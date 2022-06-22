package services

import (
	"go.mongodb.org/mongo-driver/bson"
	"time"

	"detector/pkg/config"
	metrics "github.com/hanaboso/go-metrics/pkg"

	log "github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-mongodb"
)

type Sender struct {
	metrics    metrics.Interface
	workQueue  <-chan interface{}
	logger     log.Logger
	connection *mongodb.Connection
}

type Queue struct {
	Messages  int    `json:"messages"`
	Consumers int    `json:"consumers"`
	Name      string `json:"name"`
}

type Container struct {
	Name    string `json:"name"`
	Message string `json:"message"`
	Up      bool   `json:"up"`
}

func (s *Sender) Start() {
	for data := range s.workQueue {
		now := time.Now()
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

				if err := s.metrics.Send(config.Metrics.ConsumerMeasurement, map[string]interface{}{
					"queue": queue.Name,
				}, map[string]interface{}{
					"consumers": queue.Consumers,
					"created":   time.Now(),
				}); err != nil {
					s.logContext().Error(err)
				}
				// remove old data
				_, _ = s.connection.Database.Collection(config.Metrics.ConsumerMeasurement).DeleteMany(nil, bson.M{
					"fields.created": bson.M{"$lt": now},
				})
			}
		case []Container:
			for _, container := range typed {
				if err := s.metrics.Send(config.Metrics.ContainerMeasurement, map[string]interface{}{}, map[string]interface{}{
					"name":    container.Name,
					"message": container.Message,
					"up":      container.Up,
					"created": time.Now(),
				}); err != nil {
					s.logContext().Error(err)
				}
			}
			// remove old data
			_, _ = s.connection.Database.Collection(config.Metrics.ContainerMeasurement).DeleteMany(nil, bson.M{
				"fields.created": bson.M{"$lt": now},
			})
		default:
		}
	}
}

func NewSenderSvc(workQueue <-chan interface{}) Sender {
	db := &mongodb.Connection{}
	db.Connect(config.Metrics.Dsn)

	return Sender{
		metrics:    metrics.Connect(config.Metrics.Dsn),
		connection: db,
		workQueue:  workQueue,
		logger:     config.Logger,
	}
}

func (s *Sender) logContext() log.Logger {
	return s.logger.WithFields(map[string]interface{}{
		"service": "detector",
		"type":    "sender",
	})
}
