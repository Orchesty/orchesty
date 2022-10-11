package service

import (
	"encoding/json"
	"fmt"
	"net/http"

	"cron/pkg/config"
	"cron/pkg/sender"

	log "github.com/hanaboso/go-log/pkg"
)

type (
	StartingPointService interface {
		IsConnected() bool
		RunTopology(topology, node, parameters string) error
	}

	startingPointService struct {
		sender sender.HttpSender
		logger log.Logger
		apiKey string
	}
)

func NewStartingPointService(connection sender.HttpSender, logger log.Logger, apiKey string) StartingPointService {
	return startingPointService{connection, logger, apiKey}
}

func (service startingPointService) IsConnected() bool {
	return service.sender.IsConnected()
}

func (service startingPointService) RunTopology(topology, node, parameters string) error {
	_, err := service.sender.Send(
		http.MethodPost,
		fmt.Sprintf("topologies/%s/nodes/%s/run", topology, node),
		service.createContent(parameters),
		service.createHeaders(),
	)

	if err != nil {
		service.logContext().Error(err)
	}

	return err
}

func (service startingPointService) createContent(parameters string) interface{} {
	var content interface{}

	if parameters != "" {
		if err := json.Unmarshal([]byte(fmt.Sprintf("{%s}", parameters)), &content); err != nil {
			service.logContext().Error(err)
		}
	}

	return content
}

func (service startingPointService) createHeaders() map[string]string {
	if service.apiKey != "" {
		return map[string]string{
			config.OrchestyApiKeyHeader: service.apiKey,
		}
	}

	return map[string]string{}
}

func (service startingPointService) logContext() log.Logger {
	return service.logger.WithFields(map[string]interface{}{
		"service": "CRON",
		"type":    "StartingPoint",
	})
}
