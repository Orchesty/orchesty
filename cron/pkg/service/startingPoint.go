package service

import (
	"encoding/json"
	"fmt"
	"net/http"

	"cron/pkg/config"
	"cron/pkg/sender"
	"cron/pkg/storage"

	log "github.com/hanaboso/go-log/pkg"
)

type (
	StartingPointService interface {
		IsConnected() bool
		RunTopology(topology, node, parameters string) error
	}

	startingPointService struct {
		storage storage.MongoStorage
		sender  sender.HttpSender
		logger  log.Logger
	}
)

func NewStartingPointService(connection sender.HttpSender, logger log.Logger, mongoStorage storage.MongoStorage) StartingPointService {
	return startingPointService{mongoStorage, connection, logger}
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
	jsonParameters := "{}"

	if parameters != "" {
		jsonParameters = fmt.Sprintf("{%s}", parameters)
	}

	var content interface{}

	if err := json.Unmarshal([]byte(jsonParameters), &content); err != nil {
		service.logContext().Error(err)

		return map[string]interface{}{}
	}

	return content
}

func (service startingPointService) createHeaders() map[string]string {
	if apiToken, err := service.storage.FindApiToken("orchesty", []string{"topology:run"}); err == nil {
		return map[string]string{
			config.OrchestyApiKeyHeader: apiToken.Key,
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
