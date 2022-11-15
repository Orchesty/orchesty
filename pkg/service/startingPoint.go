package service

import (
	"cron/pkg/storage"
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

func NewStartingPointService(connection sender.HttpSender, logger log.Logger, mongoStorage storage.MongoStorage) (StartingPointService, error) {
	var apiToken, err = mongoStorage.FindOneApiToken("orchesty", []string{"topology:run"})

	if err != nil {
		return nil, err
	}
	apiKey := apiToken.Key

	return startingPointService{connection, logger, apiKey}, err
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
