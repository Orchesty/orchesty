package counter

import (
	"context"
	metrics "github.com/hanaboso/go-metrics/pkg"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/model"
	"github.com/hanaboso/pipes/counter/pkg/mongo"
	"github.com/hanaboso/pipes/counter/pkg/rabbit"
	"github.com/hanaboso/pipes/counter/pkg/utils/intx"
	"github.com/hanaboso/pipes/counter/pkg/utils/timex"
	"go.mongodb.org/mongo-driver/bson"
	md "go.mongodb.org/mongo-driver/mongo"
	"sync"
	"time"
)

type MultiCounter struct {
	rabbit       *rabbit.RabbitMq
	mongo        mongo.MongoDb
	consumer     *rabbit.Consumer
	metrics      metrics.Interface
	toCommit     bool
	lastTag      uint64
	wg           *sync.WaitGroup
	processes    []md.WriteModel
	subProcesses []md.WriteModel
	finishes     []md.WriteModel
	errors       []bson.M
}

var relieve = 10

func NewMultiCounter(rabbit *rabbit.RabbitMq, mongo mongo.MongoDb) MultiCounter {
	return MultiCounter{
		rabbit:       rabbit,
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
	consumer := c.rabbit.NewConsumer("pipes.multi-counter")
	c.consumer = consumer
	msgs := consumer.Consume(ctx)
	config.Log.Info("Consumer started")

	for {
		select {
		case <-ctx.Done():
			c.commit()
			return
		case msg, ok := <-msgs:
			if ok {
				c.lastTag = msg.Tag
				if msg.Ok {
					c.wg.Add(1)
					c.processMessage(msg) // Just DO NOT make it as a new goroutine! Would create data race.
				}
			} else {
				c.commit()
				return
			}
		default:
			c.commit()
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

func (c *MultiCounter) commit() {
	if c.toCommit {
		c.wg.Wait()
		finished := c.mongo.UpdateProcesses(c.processes, c.subProcesses, c.finishes, c.errors)
		for _, process := range finished {
			go c.finishProcess(process)
		}
		c.clear()
		c.consumer.MutliAck(c.lastTag)
		relieve = 10
	} else {
		time.Sleep(time.Duration(relieve) * time.Millisecond)
		relieve = intx.Max(relieve+20, 1000)
	}
}

func (c *MultiCounter) finishProcess(process model.Process) {
	errs, _ := c.mongo.FetchErrorMessages(process.Id)
	sendFinishedProcess(process, errs)
	c.sendMetrics(process)
}

func (c *MultiCounter) sendMetrics(process model.Process) {
	err := c.metrics.Send(
		config.Metrics.Measurement,
		map[string]interface{}{
			"topology_id": process.TopologyId,
		},
		map[string]interface{}{
			"result":     process.IsFinished(),
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
