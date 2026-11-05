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
	confirmOutputYes = "yes"
	confirmOutputNo  = "no"
	confirmCallbackTimeout = 15 * time.Second
)

type Confirm struct {
	httpBeforeProcess
	broadcastAfterProcess
	mongodb *mongo.MongoDb
	client  http.Client
}

type pulseConfirmEvent struct {
	Kind     string          `json:"kind"`
	TaskID   string          `json:"task_id"`
	Question string          `json:"question"`
	Data     json.RawMessage `json:"data,omitempty"`
}

func (c Confirm) BeforeProcess(node types.Node, dto *model.ProcessMessage) model.ProcessResult {
	source := dto.GetHeaderOrDefault(enum.Header_Source, "")
	identity := dto.GetHeaderOrDefault(enum.Header_Identity, "")
	shouldPark := source == enum.Source_Pulse && identity != ""

	state, err := dto.GetHeader(enum.Header_UserTaskState)
	if err != nil {
		if !shouldPark {
			return routeConfirm(node, dto, confirmOutputYes)
		}

		originalBody := append([]byte(nil), dto.GetBody()...)
		if node.Settings().Url != "" && node.Settings().ActionPath != "" {
			if result := c.httpBeforeProcess.BeforeProcess(node, dto); !result.IsOk() {
				return result
			}
			dto.Body = originalBody
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

	dto.ClearHeaders()
	if state == enum.UserTask_Accept {
		return routeConfirm(node, dto, confirmOutputYes)
	}

	return routeConfirm(node, dto, confirmOutputNo)
}

func routeConfirm(node types.Node, dto *model.ProcessMessage, output string) model.ProcessResult {
	ids := ""
	if router, ok := node.(types.FollowerRouter); ok {
		ids = router.FollowerIdsForOutput(output)
	}
	if ids == "" {
		dto.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_DoNotContinue))
		return dto.Ok()
	}

	dto.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_ForwardToQueue))
	dto.SetHeader(enum.Header_ForceTargetQueue, ids)
	return dto.Ok()
}

func (c Confirm) notifyConfirm(dto *model.ProcessMessage, taskID, question, displayRaw string) {
	replyTo := dto.GetHeaderOrDefault(enum.Header_ReplyTo, "")
	if replyTo == "" {
		return
	}

	event := pulseConfirmEvent{
		Kind:     "confirm",
		TaskID:   taskID,
		Question: question,
		Data:     json.RawMessage(displayRaw),
	}
	if !json.Valid(event.Data) {
		event.Data = json.RawMessage("{}")
	}

	body, err := json.Marshal(event)
	if err != nil {
		log.Error().EmbedObject(dto).Err(err).Msg("confirm: failed to encode callback")
		return
	}

	req, err := http.NewRequest(http.MethodPost, replyTo, bytes.NewReader(body))
	if err != nil {
		log.Error().EmbedObject(dto).Err(err).Msg("confirm: failed to build callback request")
		return
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set(enum.Header_PulseEvent, "confirm")
	if replyID := dto.GetHeaderOrDefault(enum.Header_ReplyId, ""); replyID != "" {
		req.Header.Set(enum.Header_ReplyId, replyID)
	}

	ctx, cancel := context.WithTimeout(context.Background(), confirmCallbackTimeout)
	defer cancel()
	req = req.WithContext(ctx)

	resp, err := c.client.Do(req)
	if err != nil {
		log.Error().EmbedObject(dto).Err(err).Msgf("confirm: callback POST to %s failed", replyTo)
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

