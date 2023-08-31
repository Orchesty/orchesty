package service

import (
	"github.com/go-co-op/gocron"
	"github.com/hanaboso/go-mongodb"
)

type (
	StatusService interface {
		Status() map[string]interface{}
	}

	statusService struct {
		connection    *mongodb.Connection
		scheduler     *gocron.Scheduler
		startingPoint StartingPointService
	}
)

func NewStatusService(connection *mongodb.Connection, scheduler *gocron.Scheduler, startingPointService StartingPointService) StatusService {
	return statusService{connection, scheduler, startingPointService}
}

func (service statusService) Status() map[string]interface{} {
	return map[string]interface{}{
		"database":      service.connection.IsConnected(),
		"scheduler":     service.scheduler.IsRunning(),
		"startingPoint": service.startingPoint.IsConnected(),
	}
}
