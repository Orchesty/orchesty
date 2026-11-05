package worker

import (
	"bytes"
	"context"
	"encoding/json"
	"net/http"
	"strconv"
	"strings"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"github.com/rs/zerolog/log"
	"go.mongodb.org/mongo-driver/v2/bson"
)

const (
	confirmOutputYes     = "yes"
	confirmOutputNo      = "no"
	pulseCallbackTimeout = 15 * time.Second
)

// userTaskStore is the slice of *mongo.MongoDb the pulse user-task workers
// (Confirm, Question) need; an interface so they can be unit-tested.
type userTaskStore interface {
	StoreUserTask(dto model.ProcessResult, nodeName, topologyName string, extras ...bson.M) (bson.ObjectID, error)
}

type Confirm struct {
	httpBeforeProcess
	broadcastAfterProcess
	mongodb userTaskStore
	client  http.Client
}

type pulseConfirmEvent struct {
	Kind     string          `json:"kind"`
	TaskID   string          `json:"task_id"`
	Question string          `json:"question"`
	Data     json.RawMessage `json:"data,omitempty"`
}

func (c Confirm) BeforeProcess(node types.Node, dto *model.ProcessMessage) model.ProcessResult {
	if decision, err := dto.GetHeader(enum.Header_PulseDecision); err == nil {
		dto.ClearHeaders()
		if decision == enum.UserTask_Accept {
			return routeConfirm(node, dto, confirmOutputYes)
		}

		return routeConfirm(node, dto, confirmOutputNo)
	}

	// Retry / reject of a trashed message is not a decision: a retry asks again, a reject discards the run
	if state, err := dto.GetHeader(enum.Header_UserTaskState); err == nil {
		dto.ClearHeaders()
		if state != enum.UserTask_Accept {
			return stopRun(dto)
		}
	}

	identity, shouldPark := pulseIdentity(dto)
	if !shouldPark {
		return routeConfirm(node, dto, confirmOutputYes)
	}

	if result := callSdkKeepingBody(c.httpBeforeProcess, node, dto); !result.IsOk() {
		return result
	}

	question := dto.GetHeaderOrDefault(enum.Header_ConfirmQuestion, "")
	displayRaw := dto.GetHeaderOrDefault(enum.Header_ConfirmDisplay, "{}")
	var display any
	if err := json.Unmarshal([]byte(displayRaw), &display); err != nil {
		display = map[string]any{}
	}
	dto.DeleteHeader(enum.Header_ConfirmQuestion)
	dto.DeleteHeader(enum.Header_ConfirmDisplay)

	taskID, storeErr := c.mongodb.StoreUserTask(dto.Ok(), node.NodeName(), node.TopologyName(), bson.M{
		"type":     mongo.Type_Confirm,
		"identity": identity,
		"question": question,
		"display":  display,
		"replyId":  dto.GetHeaderOrDefault(enum.Header_ReplyId, ""),
	})
	if storeErr != nil {
		return dto.Error(storeErr)
	}

	c.notifyConfirm(dto, taskID.Hex(), question, displayRaw)
	return dto.Pending()
}

func routeConfirm(node types.Node, dto *model.ProcessMessage, output string) model.ProcessResult {
	ids := ""
	if router, ok := node.(types.FollowerRouter); ok {
		ids = router.FollowerIdsForOutput(output)
	}
	if ids == "" {
		return stopRun(dto)
	}

	dto.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_ForwardToQueue))
	dto.SetHeader(enum.Header_ForceTargetQueue, ids)
	return dto.Ok()
}

func (c Confirm) notifyConfirm(dto *model.ProcessMessage, taskID, question, displayRaw string) {
	event := pulseConfirmEvent{
		Kind:     "confirm",
		TaskID:   taskID,
		Question: question,
		Data:     json.RawMessage(displayRaw),
	}
	if !json.Valid(event.Data) {
		event.Data = json.RawMessage("{}")
	}

	postPulseEvent(c.client, dto, "confirm", event)
}

// pulseIdentity reports whether the run was started by Pulse on behalf of a
// real user; only such runs can be parked and answered from the chat.
func pulseIdentity(dto *model.ProcessMessage) (string, bool) {
	identity := dto.GetHeaderOrDefault(enum.Header_Identity, "")

	return identity, dto.GetHeaderOrDefault(enum.Header_Source, "") == enum.Source_Pulse && identity != ""
}

// callSdkKeepingBody runs the node's SDK action (if configured) only for the
// headers it sets; the original body is restored afterwards.
func callSdkKeepingBody(h httpBeforeProcess, node types.Node, dto *model.ProcessMessage) model.ProcessResult {
	if node.Settings().Url == "" || node.Settings().ActionPath == "" {
		return dto.Ok()
	}

	originalBody := append([]byte(nil), dto.GetBody()...)
	result := h.BeforeProcess(node, dto)
	if result.IsOk() {
		dto.Body = originalBody
	}

	return result
}

// stopRun ends the run successfully without publishing to any follower.
func stopRun(dto *model.ProcessMessage) model.ProcessResult {
	dto.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_DoNotContinue))

	return dto.Ok()
}

// postPulseEvent POSTs a JSON event to the run's reply-to callback (best effort).
func postPulseEvent(client http.Client, dto *model.ProcessMessage, kind string, event any) {
	replyTo := dto.GetHeaderOrDefault(enum.Header_ReplyTo, "")
	if replyTo == "" {
		return
	}

	body, err := json.Marshal(event)
	if err != nil {
		log.Error().EmbedObject(dto).Err(err).Msgf("%s: failed to encode callback", kind)
		return
	}

	ctx, cancel := context.WithTimeout(context.Background(), pulseCallbackTimeout)
	defer cancel()

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, replyTo, bytes.NewReader(body))
	if err != nil {
		log.Error().EmbedObject(dto).Err(err).Msgf("%s: failed to build callback request", kind)
		return
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set(enum.Header_PulseEvent, kind)
	if replyID := dto.GetHeaderOrDefault(enum.Header_ReplyId, ""); replyID != "" {
		req.Header.Set(enum.Header_ReplyId, replyID)
	}

	resp, err := client.Do(req)
	if err != nil {
		log.Error().EmbedObject(dto).Err(err).Msgf("%s: callback POST to %s failed", kind, replyTo)
		return
	}
	defer resp.Body.Close()
}

func FollowerIdsForOutput(followers []model.Follower, output string) string {
	var ids []string
	for _, follower := range followers {
		if follower.Output == output || (output == confirmOutputYes && follower.Output == "") {
			ids = append(ids, follower.Id)
		}
	}

	return strings.Join(ids, ",")
}
