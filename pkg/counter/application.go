package counter

import (
	"context"
	"sync"
	"time"

	metrics "github.com/hanaboso/go-metrics/pkg"
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"github.com/hanaboso/go-utils/pkg/intx"
	"github.com/hanaboso/go-utils/pkg/timex"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/enum"
	"github.com/hanaboso/pipes/counter/pkg/model"
	"github.com/hanaboso/pipes/counter/pkg/mongo"
	"github.com/hanaboso/pipes/counter/pkg/rabbit"
	amqp "github.com/rabbitmq/amqp091-go"
	"go.mongodb.org/mongo-driver/v2/bson"
	md "go.mongodb.org/mongo-driver/v2/mongo"
)

type MultiCounter struct {
	rabbitmq   *rabbitmq.Client
	events     *rabbitmq.Publisher
	mongo      mongo.MongoDb
	metrics    metrics.Interface
	toCommit   bool
	wg         *sync.WaitGroup
	agg        map[string]*procAggregate
	subAgg     map[string]*procAggregate
	errors     []bson.M
	batchCount int
}

type procAggregate struct {
	sample    *model.ParsedMessage
	ok        int
	nok       int
	processed int
	total     int
	audit     map[string]struct{}
}

var relieve = 10

func NewMultiCounter(rabbitmq *rabbitmq.Client, events *rabbitmq.Publisher, mongo mongo.MongoDb) MultiCounter {
	return MultiCounter{
		rabbitmq: rabbitmq,
		events:   events,
		mongo:    mongo,
		metrics:  metrics.Connect(config.Metrics.Dsn),
		toCommit: false,
		agg:      map[string]*procAggregate{},
		subAgg:   map[string]*procAggregate{},
		errors:   nil,
		wg:       &sync.WaitGroup{},
	}
}

func (c *MultiCounter) Start(ctx context.Context) {
	consumer := c.rabbitmq.NewConsumer("pipes.multi-counter", config.RabbitMq.Prefetch)
	msgs := consumer.Consume(false)
	config.Log.Info("Consumer started")
	var lastMessage amqp.Delivery

	linger := time.Duration(config.App.CommitDelayMs) * time.Millisecond
	maxBatch := config.RabbitMq.Prefetch
	handle := func(msg amqp.Delivery) {
		lastMessage = msg
		parsed := rabbit.ParseMessage(msg)

		if parsed.Ok {
			c.wg.Add(1)
			c.processMessage(parsed)
		}
	}

	for {
		select {
		case <-ctx.Done():
			c.commit(lastMessage)
			return
		case msg, ok := <-msgs:
			if !ok {
				c.commit(lastMessage)
				return
			}
			handle(msg)

			if maxBatch > 0 && c.batchCount >= maxBatch {
				c.commit(lastMessage)
			}
		default:
			if linger <= 0 {
				c.commit(lastMessage)
				continue
			}

			select {
			case <-ctx.Done():
				c.commit(lastMessage)
				return
			case msg, ok := <-msgs:
				if !ok {
					c.commit(lastMessage)
					return
				}

				handle(msg)

				if maxBatch > 0 && c.batchCount >= maxBatch {
					c.commit(lastMessage)
				}
			case <-time.After(linger):
				c.commit(lastMessage)
			}
		}
	}
}

func (c *MultiCounter) processMessage(message *model.ParsedMessage) {
	defer c.wg.Done()
	c.toCommit = true
	c.batchCount++

	corr := message.ProcessMessage.GetHeaderOrDefault(enum.Header_CorrelationId, "")
	a := c.agg[corr]

	if a == nil {
		a = &procAggregate{audit: map[string]struct{}{}}
		c.agg[corr] = a
	}

	a.sample = message
	body := message.ProcessMessage.ProcessBody

	if body.Success {
		a.ok++
	} else {
		a.nok++
	}

	a.processed++
	a.total += body.Following

	for _, id := range message.AuditIds() {
		a.audit[id] = struct{}{}
	}

	if config.App.EnableSubProcessCounters {
		pid := message.ProcessMessage.GetHeaderOrDefault(enum.Header_ProcessId, "")
		s := c.subAgg[pid]

		if s == nil {
			s = &procAggregate{}
			c.subAgg[pid] = s
		}

		s.sample = message

		if body.Success {
			s.ok++
		} else {
			s.nok++
		}

		s.processed++
		s.total += body.Following
	}

	if !body.Success {
		c.errors = append(c.errors, message.ErrorDoc())
	}
}

func (c *MultiCounter) commit(msg amqp.Delivery) {
	if msg.DeliveryTag > 0 && c.toCommit {
		c.wg.Wait()

		processes := make([]md.WriteModel, 0, len(c.agg)*2)
		finishes := make([]md.WriteModel, 0, len(c.agg))
		finishesProcesses := make([]string, 0, len(c.agg))
		var auditDataLinks []interface{}
		now := time.Now()
		for corr, a := range c.agg {
			audit := make([]string, 0, len(a.audit))

			for id := range a.audit {
				audit = append(audit, id)
			}

			auditDataLinks = append(auditDataLinks, model.AuditDataLinks(now, corr, audit)...)
			processes = append(
				processes,
				a.sample.ProcessInitQuery(),
				model.MergedProcessQuery(corr, a.ok, a.nok, a.processed, a.total),
			)
			finishes = append(finishes, a.sample.FinishProcessQuery())
			finishesProcesses = append(finishesProcesses, corr)
		}

		var subProcesses []md.WriteModel
		if config.App.EnableSubProcessCounters && len(c.subAgg) > 0 {
			subProcesses = make([]md.WriteModel, 0, len(c.subAgg)*2)

			for pid, s := range c.subAgg {
				subProcesses = append(
					subProcesses,
					s.sample.SubProcessInitQuery(),
					model.MergedSubProcessQuery(pid, s.ok, s.nok, s.processed, s.total),
				)
			}
		}

		finished := c.mongo.UpdateProcesses(processes, subProcesses, finishes, c.errors, finishesProcesses, auditDataLinks)
		for _, process := range finished {
			go c.finishProcess(process, msg.Headers)
		}
		c.clear()
		_ = msg.Ack(true)
		relieve = 10
	} else {
		time.Sleep(time.Duration(relieve) * time.Millisecond)
		relieve = intx.Max(relieve+20, 1000)
	}
}

func (c *MultiCounter) finishProcess(process model.Process, headers amqp.Table) {
	errs, _ := c.mongo.FetchErrorMessages(process.Id)
	apiToken, err := c.mongo.GetApiToken("orchesty", []string{"topology:run"})

	if err != nil {
		config.Log.Warn("Unable to fetch orchesty ApiKey to process topology result", err)
		return
	}

	apiKey := apiToken.Key

	topology, _ := c.mongo.GetTopology(process.TopologyId)
	if topology.Name != "" {
		sendFinishedProcess(process, errs, apiKey, headers, topology)
		c.sendMetrics(process)
		sendEvents(c.events, process, topology)
	}
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
	c.agg = map[string]*procAggregate{}
	c.subAgg = map[string]*procAggregate{}
	c.errors = nil
	c.batchCount = 0
}
