package bridge

import (
	"context"
	metrics "github.com/hanaboso/go-metrics/pkg"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"sync"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/enum"

	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/rabbitmq"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
)

const defaultTimeout = 60 * time.Second

type Bridge struct {
	// Should be a max of a node's timeout
	timeout  time.Duration
	topology model.Topology
	// External services
	rabbitMQ *rabbitmq.RabbitMQ
	limiter  *limiter
	repeater *repeater
	mongodb  *mongo.MongoDb
	metrics  metrics.Interface
	counter  counter
}

func (b *Bridge) Run(ctx context.Context) {
	b.rabbitMQ.AwaitStartup()
	b.timeout = b.topology.Timeout
	if b.timeout <= 0 {
		b.timeout = defaultTimeout
	}

	b.start(ctx)
}

func NewBridge(rabbit *rabbitmq.RabbitMQ, mongodb *mongo.MongoDb, topology model.Topology) Bridge {
	return Bridge{
		topology: topology,
		rabbitMQ: rabbit,
		limiter:  newLimiter(rabbit.Limiter),
		repeater: newRepeater(rabbit.Repeater),
		mongodb:  mongodb,
		metrics:  metrics.Connect(config.Metrics.Dsn),
		counter:  newCounter(rabbit.Counter),
	}
}

func (b *Bridge) start(ctx context.Context) {
	workerWg := &sync.WaitGroup{}
	for _, node := range b.topology.Shards {
		// Starts only one worker per node
		if node.Index != 1 {
			continue
		}

		workerWg.Add(1)
		go func(shard model.NodeShard, wg *sync.WaitGroup) {
			worker := newNode(*shard.Node, b.topology.ID, b.topology.Name, *b.rabbitMQ, wg, b.limiter, b.repeater, b.mongodb, b.metrics, b.counter)
			worker.start()
		}(node, workerWg)
	}

	log.Info().EmbedObject(b).Msg("bridge started")

	// Wait for terminating signal
	<-ctx.Done()

	// Shutdown logic
	shutdownCtx, cancel := context.WithTimeout(context.Background(), b.timeout)
	defer cancel()

	b.shutdown(shutdownCtx, workerWg)
	log.Info().EmbedObject(b).Msg("bridge stopped")
}

func (b *Bridge) shutdown(ctx context.Context, wg *sync.WaitGroup) {
	done := make(chan struct{})

	go func() {
		defer close(done)

		b.rabbitMQ.CloseSubscribers()

		// Awaits rabbitMq nodes to process remaining messages
		log.Debug().Msg("awaiting rabbitMq workers to finish processes...")
		wg.Wait()

		b.rabbitMQ.ClosePublishers()
		b.mongodb.Close()
	}()

	select {
	case <-done:
		return // Nodes closed within time limit
	case <-ctx.Done():
		log.Warn().EmbedObject(b).Interface(enum.LogHeader_Data, model.LogData{"timeout": b.timeout.Seconds()}).Msg("bridge shutdown took longer than expected")
	}
}

// Adds topologyId -> best to use as .EmbedObject(b)
func (b Bridge) MarshalZerologObject(e *zerolog.Event) {
	if b.topology.ID != "" {
		e.Str(enum.LogHeader_TopologyId, b.topology.ID)
	}
}
