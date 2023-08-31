package counter

import (
	"context"
	metrics "github.com/hanaboso/go-metrics/pkg"
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"github.com/hanaboso/go-utils/pkg/intx"
	"github.com/hanaboso/go-utils/pkg/timex"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/model"
	"github.com/hanaboso/pipes/counter/pkg/mongo"
	"github.com/hanaboso/pipes/counter/pkg/rabbit"
	amqp "github.com/rabbitmq/amqp091-go"
	"go.mongodb.org/mongo-driver/bson"
	md "go.mongodb.org/mongo-driver/mongo"
	"sync"
	"time"
)

type MultiCounter struct {
	rabbitmq     *rabbitmq.Client
	mongo        mongo.MongoDb
	metrics      metrics.Interface
	toCommit     bool
	wg           *sync.WaitGroup
	processes    []md.WriteModel
	subProcesses []md.WriteModel
	finishes     []md.WriteModel
	errors       []bson.M
}

var relieve = 10

func NewMultiCounter(rabbitmq *rabbitmq.Client, mongo mongo.MongoDb) MultiCounter {
	return MultiCounter{
		rabbitmq:     rabbitmq,
		mongo:        mongo,
		metrics:      metrics.Connect(config.Metrics.Dsn),
		toCommit:     false,
		processes:    nil,
		subProcesses: nil,
		finishes:     nil,
		errors:       nil,
		wg:           &sync.WaitGroup{},
	}
}

func (c *MultiCounter) Start(ctx context.Context) {
	consumer := c.rabbitmq.NewConsumer("pipes.multi-counter", config.RabbitMq.Prefetch)
	msgs := consumer.Consume(false)
	config.Log.Info("Consumer started")
	var lastMessage amqp.Delivery

	for {
		select {
		case <-ctx.Done():
			c.commit(lastMessage)
			return
		case msg, ok := <-msgs:
			if ok {
				lastMessage = msg
				parsed := rabbit.ParseMessage(msg)
				if parsed.Ok {
					c.wg.Add(1)
					c.processMessage(parsed) // Just DO NOT make it as a new goroutine! Would create data race.
				}
			} else {
				c.commit(lastMessage)
				return
			}
		default:
			c.commit(lastMessage)
		}
	}
}

func (c *MultiCounter) processMessage(message *model.ParsedMessage) {
	defer c.wg.Done()
	c.toCommit = true

	c.processes = append(
		c.processes,
		message.ProcessInitQuery(),
		message.ProcessQuery(),
	)
	c.subProcesses = append(
		c.processes,
		message.SubProcessInitQuery(),
		message.SubProcessQuery(),
	)

	c.finishes = append(c.finishes, message.FinishProcessQuery())
	if !message.ProcessMessage.ProcessBody.Success {
		c.errors = append(c.errors, message.ErrorDoc())
	}
}

func (c *MultiCounter) commit(msg amqp.Delivery) {
	if msg.DeliveryTag > 0 && c.toCommit {
		c.wg.Wait()
		finished := c.mongo.UpdateProcesses(c.processes, c.subProcesses, c.finishes, c.errors)
		for _, process := range finished {
			go c.finishProcess(process)
		}
		c.clear()
		_ = msg.Ack(true)
		relieve = 10
	} else {
		time.Sleep(time.Duration(relieve) * time.Millisecond)
		relieve = intx.Max(relieve+20, 1000)
	}
}

func (c *MultiCounter) finishProcess(process model.Process) {
	errs, _ := c.mongo.FetchErrorMessages(process.Id)
	apiToken, err := c.mongo.GetApiToken("orchesty", []string{"topology:run"})

	if err != nil {
		return
	}

	apiKey := apiToken.Key

	sendFinishedProcess(process, errs, apiKey)
	c.sendMetrics(process)
}

func (c *MultiCounter) sendMetrics(process model.Process) {
	err := c.metrics.Send(
		config.Metrics.Measurement,
		map[string]interface{}{
			"topology_id": process.TopologyId,
		},
		map[string]interface{}{
			"result":     process.IsOk(),
			"duration":   timex.MsDiff(process.Created, time.Now()),
			"ok_count":   process.Ok,
			"fail_count": process.Nok,
			"created":    time.Now(),
		},
	)
	if err != nil {
		config.Log.Error(err)
	}
}

func (c *MultiCounter) clear() {
	c.toCommit = false
	c.processes = nil
	c.subProcesses = nil
	c.finishes = nil
	c.errors = nil
}
