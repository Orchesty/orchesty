package bridge

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"net/http"
	"time"
)

const (
	status_topology = "system-events"
	status_node     = "start"
)

var client = http.Client{}

func sendFinishedProcess(process *model.ProcessMessage, status string, trashId *string) {
	if process.GetBoolHeaderOrDefault(enum.Header_SystemEvent, false) {
		return
	}

	message := model.StatusMessage{
		Type: status,
		Data: model.StatusMessageData{
			TopologyId:    process.GetHeaderOrDefault(enum.Header_TopologyId, ""),
			ResultMessage: process.GetHeaderOrDefault(enum.Header_ResultMessage, "Message thrown into trash"),
			CorrelationId: process.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
			ProcessId:     process.GetHeaderOrDefault(enum.Header_ProcessId, ""),
			User:          process.GetHeaderOrDefault(enum.Header_User, ""),
			TimestampMs:   process.Published,
		},
		Contents: []model.StatusMessageContent{
			{
				TrashId: trashId,
				Body:    string(process.Body),
			},
		},
	}

	body, err := json.Marshal(message)
	if err != nil {
		return
	}

	req, err := http.NewRequest(
		"POST",
		fmt.Sprintf("%s/topologies/%s/nodes/%s/run-by-name", config.StartingPoint.Dsn, status_topology, status_node),
		bytes.NewBuffer(body),
	)
	if err != nil {
		return
	}

	ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
	defer cancel()
	req = req.WithContext(ctx)

	response, err := client.Do(req)
	if err != nil {
		return
	}

	_ = response.Body.Close()
}
