package worker

import (
	"encoding/json"
	"net/http"

	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"go.mongodb.org/mongo-driver/v2/bson"
)

// Question pauses a Pulse run and asks the user a free-text question; Pulse
// turns the answer into structured args (validated against the schema),
// merges them into the body and resumes the run with user-task-state: accept.
// Non-Pulse runs and SDK-requested skips pass straight through.
type Question struct {
	httpBeforeProcess
	broadcastAfterProcess
	mongodb userTaskStore
	client  http.Client
}

type pulseQuestionEvent struct {
	Kind     string          `json:"kind"`
	TaskID   string          `json:"task_id"`
	Question string          `json:"question"`
	Schema   json.RawMessage `json:"schema"`
}

func (q Question) BeforeProcess(node types.Node, dto *model.ProcessMessage) model.ProcessResult {
	if decision, err := dto.GetHeader(enum.Header_PulseDecision); err == nil {
		dto.ClearHeaders()
		if decision == enum.UserTask_Accept {
			return dto.Ok()
		}

		return stopRun(dto)
	}

	// Retry / reject of a trashed message is not an answer: a retry asks again, a reject discards the run
	if state, err := dto.GetHeader(enum.Header_UserTaskState); err == nil {
		dto.ClearHeaders()
		if state != enum.UserTask_Accept {
			return stopRun(dto)
		}
	}

	identity, isPulse := pulseIdentity(dto)
	if !isPulse {
		return dto.Ok()
	}

	if result := callSdkKeepingBody(q.httpBeforeProcess, node, dto); !result.IsOk() {
		return result
	}
	if result, done := sdkDidNotSucceed(dto); done {
		return result
	}

	question := dto.GetHeaderOrDefault(enum.Header_QuestionText, "")
	schemaRaw := dto.GetHeaderOrDefault(enum.Header_QuestionSchema, "")
	skip := dto.GetHeaderOrDefault(enum.Header_QuestionSkip, "")
	dto.DeleteHeader(enum.Header_QuestionText)
	dto.DeleteHeader(enum.Header_QuestionSchema)
	dto.DeleteHeader(enum.Header_QuestionSkip)

	if skip == "1" {
		return dto.Ok()
	}

	schema := map[string]any{}
	if err := json.Unmarshal([]byte(schemaRaw), &schema); err != nil || schema == nil {
		schema = map[string]any{}
	}
	schemaJson, _ := json.Marshal(schema)

	taskID, storeErr := q.mongodb.StoreUserTask(dto.Ok(), node.NodeName(), node.TopologyName(), bson.M{
		"type":     mongo.Type_Question,
		"identity": identity,
		"question": question,
		"schema":   schema,
		"replyId":  dto.GetHeaderOrDefault(enum.Header_ReplyId, ""),
	})
	if storeErr != nil {
		return dto.Error(storeErr)
	}

	PostReplyEvent(q.client, dto, "question", pulseQuestionEvent{
		Kind:     "question",
		TaskID:   taskID.Hex(),
		Question: question,
		Schema:   schemaJson,
	})

	return dto.Pending()
}
