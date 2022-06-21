package services

import (
	"time"

	"detector/pkg/config"
	metrics "github.com/hanaboso/go-metrics/pkg"

	log "github.com/hanaboso/go-log/pkg"
)

type Sender struct {
	metrics   metrics.Interface
	workQueue <-chan interface{}
	logger    log.Logger
}

func (s *Sender) Start() {
	for data := range s.workQueue {
		switch typed := data.(type) {
		case []Queue:
			for _, queue := range typed {
				if queue.Messages > 0 {
					if err := s.metrics.Send(config.Metrics.Measurement, map[string]interface{}{
						"queue": queue.Name,
					}, map[string]interface{}{
						"messages": queue.Messages,
						"created":  time.Now(),
					}); err != nil {
						s.logContext().Error(err)
					}
				}

				if queue.Consumers <= 0 {
					if err := s.metrics.Send(config.Metrics.ConsumerMeasurement, map[string]interface{}{
						"queue": queue.Name,
					}, map[string]interface{}{
						"consumers": 0,
						"created":   time.Now(),
					}); err != nil {
						s.logContext().Error(err)
					}
				}
			}
		case []Container:
			for _, container := range typed {
				if err := s.metrics.Send(config.Metrics.ContainerMeasurement, map[string]interface{}{}, map[string]interface{}{
					"name":    container.Name,
					"status":  container.Status,
					"created": time.Now(),
				}); err != nil {
					s.logContext().Error(err)
				}
			}
		default:
		}
	}
}

func NewSenderSvc(workQueue <-chan interface{}) Sender {
	return Sender{
		metrics:   metrics.Connect(config.Metrics.Dsn),
		workQueue: workQueue,
		logger:    config.Logger,
	}
}

func (s *Sender) logContext() log.Logger {
	return s.logger.WithFields(map[string]interface{}{
		"service": "detector",
		"type":    "sender",
	})
}
