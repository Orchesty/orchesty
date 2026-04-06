package service

import (
	"context"

	"github.com/hanaboso/go-mongodb"
	"notifier/pkg/rabbit"
)

type (
	StatusService interface {
		Status() map[string]interface{}
	}

	statusService struct {
		connection *mongodb.Connection
		rabbit     rabbit.Rabbit
		throttle   ThrottleStore
	}
)

func NewStatusService(connection *mongodb.Connection, rmq rabbit.Rabbit, throttle ThrottleStore) StatusService {
	return statusService{connection, rmq, throttle}
}

func (service statusService) Status() map[string]interface{} {
	return map[string]interface{}{
		"database": service.connection.IsConnected(),
		"rabbitmq": service.rabbit.IsConnected(),
		"redis":    service.throttle.Ping(context.Background()) == nil,
	}
}
