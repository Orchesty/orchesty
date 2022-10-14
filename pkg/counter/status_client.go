package counter

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/enum"
	"github.com/hanaboso/pipes/counter/pkg/model"
	"github.com/rs/zerolog/log"
	"net/http"
	"time"
)

const (
	status_topology = "system-events"
	status_node     = "start"
)

var client = http.Client{}

func sendFinishedProcess(process model.Process, errors []model.ErrorMessage) {
	if process.SystemEvent {
		return
	}

	messageType := enum.StatusType_ProcessSuccess
	if !process.IsOk() {
		messageType = enum.StatusType_ProcessFailed
	}

	finished := time.Now()
	if process.Finished != nil {
		finished = *process.Finished
	}

	var contents []model.StatusMessageContent
	for _, msg := range errors {
		contents = append(contents, model.StatusMessageContent{
			TrashId: nil,
			Body:    msg.Body,
		})
	}

	message := model.StatusMessage{
		Type: messageType,
		Data: model.StatusMessageData{
			TopologyId:    process.TopologyId,
			ResultMessage: "",
			CorrelationId: process.Id,
			ProcessId:     process.Id,
			User:          process.User,
			TimestampMs:   finished.UnixMilli(),
		},
		Contents: contents,
	}

	body, err := json.Marshal(message)
	if err != nil {
		log.Err(err).Send()
		return
	}

	req, err := http.NewRequest(
		"POST",
		fmt.Sprintf("%s/topologies/%s/nodes/%s/run-by-name", config.StartingPoint.Dsn, status_topology, status_node),
		bytes.NewBuffer(body),
	)
	if err != nil {
		log.Err(err).Send()
		return
	}

	req.Header.Add("Orchesty-Api-Key", config.StartingPoint.ApiKey)

	ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
	defer cancel()
	req = req.WithContext(ctx)

	response, err := client.Do(req)
	if err != nil {
		log.Err(err).Send()
		return
	}

	_ = response.Body.Close()
}
