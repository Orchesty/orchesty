package services

import (
	"encoding/json"
	"fmt"
	"net/http"
	"strings"

	"rabbitmq-telegraf/pkg/config"
)

type RabbitMqFetchSvc interface {
	GatherQueuesInfo() ([]Queue, error)
}

type RabbitMqStats struct{}

type Queue struct {
	Messages int    `json:"messages"`
	Name     string `json:"name"`
}

func (svc RabbitMqStats) GatherQueuesInfo() ([]Queue, error) {
	var list []Queue

	url := fmt.Sprintf("%s/api/queues", strings.TrimRight(config.RabbitMQ.Host, "/"))
	req, err := http.NewRequest("GET", url, nil)
	if err != nil {
		return nil, err
	}

	req.SetBasicAuth(
		config.RabbitMQ.Username,
		config.RabbitMQ.Password,
	)

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

func NewRabbitMqFetchSvc() RabbitMqFetchSvc {
	return RabbitMqStats{}
}
