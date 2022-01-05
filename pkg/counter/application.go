package counter

import (
	"context"
	"encoding/json"
	metrics "github.com/hanaboso/go-metrics/pkg"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/enum"
	"github.com/hanaboso/pipes/counter/pkg/model"
	"github.com/hanaboso/pipes/counter/pkg/mongo"
	"github.com/hanaboso/pipes/counter/pkg/rabbit"
	"github.com/hanaboso/pipes/counter/pkg/utils/intx"
	"github.com/hanaboso/pipes/counter/pkg/utils/timex"
	"github.com/rs/zerolog/log"
	"github.com/streadway/amqp"
	"time"
)

type MultiCounter struct {
	rabbit          *rabbit.RabbitMq
	mongo           *mongo.MongoDb
	processes       model.Processes
	consumer        *rabbit.Consumer
	statusPublisher *rabbit.Publisher
	toCommit        bool
	lastTag         uint64
	metrics         metrics.Interface
}

var relieve = 10

func NewMultiCounter(rabbit *rabbit.RabbitMq, mongo *mongo.MongoDb) MultiCounter {
	return MultiCounter{
		rabbit:    rabbit,
		mongo:     mongo,
		processes: mongo.LoadProcesses(),
		metrics:   metrics.Connect(config.Metrics.Dsn),
	}
}

func (c *MultiCounter) Start(ctx context.Context) {
	consumer := c.rabbit.NewConsumer("pipes.multi-counter")
	statusPublisher := c.rabbit.NewPublisher("", "pipes.results")
	c.consumer = consumer
	c.statusPublisher = statusPublisher
	msgs := consumer.Consume(ctx)
	log.Info().Msg("Consumer started")

	for {
		select {
		case <-ctx.Done():
			c.commit()
			return
		case msg, ok := <-msgs:
			if ok {
				c.lastTag = msg.Tag
				c.processMessage(msg)
			} else {
				c.commit()
				return
			}
		default:
			c.commit()
		}
	}
}

func (c *MultiCounter) processMessage(message *model.ProcessMessage) {
	var body model.ProcessBody
	if err := json.Unmarshal(message.Body, &body); err != nil {
		log.Error().Err(err).Send()
		return
	}

	c.toCommit = true
	// Upsert by correlationId
	correlationId := message.GetHeaderOrDefault(enum.Header_CorrelationId, "")
	processId := message.GetHeaderOrDefault(enum.Header_ProcessId, "")

	root, ok := c.processes[correlationId]
	if !ok {
		root = c.mongo.LoadProcess(correlationId)
		if root == nil {
			root = message.IntoProcess()
			root.Total++
		}
		c.processes[correlationId] = root
	}

	// Main process
	root.Increment(body)
	if processId != root.ProcessId {
		// Subprocess
		subprocess, ok := root.Subprocesses[processId]
		if !ok {
			subprocess = message.IntoSubprocess()
			root.Subprocesses[processId] = subprocess
			root.OpenProcesses++
		}

		subprocess.Increment(body)
		if subprocess.IsFinished() {
			closed := []string{processId}
			closed = append(closed, root.CloseSubprocess(subprocess)...)
			/*
				TODO 'closed' are finished subprocesses -> ordered from bottom up
				TODO Use it later for subprocess notifications
			*/
		}
	}

	if root.IsFinished() {
		finished := message.GetTimeHeaderOrDefault(enum.Header_PublishedTimestamp)
		root.Finished = &finished
		c.finishProcess(root)
	}
}

func (c *MultiCounter) finishProcess(process *model.Process) {
	body, _ := json.Marshal(struct {
		ProcessId string `json:"process_id"`
		Success   bool   `json:"success"`
	}{
		ProcessId: process.CorrelationId,
		Success:   process.IsOk(),
	})

	c.statusPublisher.Publish(amqp.Publishing{
		ContentType: "application/json",
		Body:        body,
	})

	c.sendMetrics(process)
}

func (c *MultiCounter) commit() {
	if c.toCommit {
		c.toCommit = false
		c.mongo.UpdateProcesses(c.processes)
		c.consumer.MutliAck(c.lastTag)
		relieve = 10
	} else {
		time.Sleep(time.Duration(relieve) * time.Millisecond)
		relieve = intx.Max(relieve+20, 1000)
	}
}

func (c *MultiCounter) sendMetrics(process *model.Process) {
	err := c.metrics.Send(
		config.Metrics.Measurement,
		map[string]interface{}{
			"topology_id": process.TopologyId,
		},
		map[string]interface{}{
			"result":     process.IsFinished(),
			"duration":   timex.MsDiff(process.Created, *process.Finished),
			"ok_count":   process.Ok,
			"fail_count": process.Nok,
		},
	)
	if err != nil {
		log.Err(err).Send()
	}
}
