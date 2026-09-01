package services

import (
	"encoding/json"
	"fmt"
	"net/http"
	"strings"

	"detector/pkg/config"
)

type RabbitMqStats struct{}

func (svc RabbitMqStats) GatherQueuesInfo() ([]Queue, error) {
	var list []Queue

	url := fmt.Sprintf(
		"http://%s:15672/api/queues/%s",
		strings.TrimRight(config.RabbitMQ.Host, "/"),
		strings.TrimLeft(config.RabbitMQ.VHost, "/"),
	)
	req, err := http.NewRequest("GET", url, nil)
	if err != nil {
		return nil, err
	}

	req.SetBasicAuth(config.RabbitMQ.Username, config.RabbitMQ.Password)

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, err
	}

	defer func() { _ = res.Body.Close() }()

	if err = json.NewDecoder(res.Body).Decode(&list); err != nil {
		return nil, err
	}

	return list, nil
}

// NewRabbitMqFetchSvc creates RabbitMq fetcher
func NewRabbitMqFetchSvc() RabbitMqStats {
	return RabbitMqStats{}
}
