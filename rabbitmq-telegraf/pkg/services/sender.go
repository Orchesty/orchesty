package services

import (
	"time"

	"github.com/hanaboso/go-metrics"
	"rabbitmq-telegraf/pkg/config"

	log "github.com/sirupsen/logrus"
)

// Sender represents RabbitMq metrics sender
type Sender struct {
	metrics   metrics.Interface
	workQueue <-chan []Queue
}

// Start RabbitMq metrics sender
func (s *Sender) Start() {
	for queues := range s.workQueue {
		for _, queue := range queues {
			if queue.Messages <= 0 {
				continue
			}

			if err := s.metrics.Send(config.Metrics.Measurement, map[string]interface{}{
				"queue": queue.Name,
			}, map[string]interface{}{
				"messages": queue.Messages,
				"created":  time.Now().Unix(),
			}); err != nil {
				log.Error(err)
			}
		}
	}
}

// NewSenderSvc creates RabbitMq metrics sender
func NewSenderSvc(workQueue <-chan []Queue) Sender {
	return Sender{
		metrics:   metrics.Connect(config.Metrics.Dsn),
		workQueue: workQueue,
	}
}
