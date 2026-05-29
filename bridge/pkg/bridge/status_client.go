package bridge

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
	"net/http"
	"time"
)

const (
	status_topology = "system-events"
	status_node     = "start"
)

var client = http.Client{}

func sendLimitOverflowStatus(limitType string, currentValue, limitValue float64, discardedCount int64, resultMessage string) {
	message := model.StatusMessage{
		Type: enum.StatusType_LimitOverflow,
		Data: model.StatusMessageData{
			ResultMessage: resultMessage,
		},
		Contents: []model.StatusMessageContent{
			{
				Body: fmt.Sprintf(`{"limit_type":"%s","current_value":%.0f,"limit_value":%.0f,"discarded_count":%d}`,
					limitType, currentValue, limitValue, discardedCount),
			},
		},
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

func sendLimitRecoveredStatus(limitType string, currentValue, limitValue float64, discardedCount int64, resultMessage string) {
	message := model.StatusMessage{
		Type: enum.StatusType_LimitRecovered,
		Data: model.StatusMessageData{
			ResultMessage: resultMessage,
		},
		Contents: []model.StatusMessageContent{
			{
				Body: fmt.Sprintf(`{"limit_type":"%s","current_value":%.0f,"limit_value":%.0f,"discarded_count":%d}`,
					limitType, currentValue, limitValue, discardedCount),
			},
		},
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

func sendFinishedProcess(process *model.ProcessMessage, status string, trashId *string, topologyName string) {
	if process.GetBoolHeaderOrDefault(enum.Header_SystemEvent, false) {
		return
	}

	message := model.StatusMessage{
		Type: status,
		Data: model.StatusMessageData{
			TopologyId:      process.GetHeaderOrDefault(enum.Header_TopologyId, ""),
			ResultMessage:   process.GetHeaderOrDefault(enum.Header_ResultMessage, "Message thrown into trash"),
			CorrelationId:   process.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
			ProcessId:       process.GetHeaderOrDefault(enum.Header_ProcessId, ""),
			User:            process.GetHeaderOrDefault(enum.Header_User, ""),
			TimestampMs:     process.Published,
			TopologyName:    topologyName,
			TopologyVersion: 0, // TODO: upravit a doplnit
			Applications:    process.GetHeaderOrDefault(enum.Header_Applications, ""),
		},
		Contents: []model.StatusMessageContent{
			{
				TrashId: trashId,
				Body:    process.GetOriginalBody(),
			},
		},
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
