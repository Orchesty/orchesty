package bridge

import (
	"context"
	"fmt"
	metrics "github.com/hanaboso/go-metrics/pkg"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"github.com/hanaboso/pipes/bridge/pkg/rabbit"
	"sync"
	"time"

	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
)

const defaultTimeout = 60 * time.Second

type Bridge struct {
	// Should be a max of a node's timeout
	timeout  time.Duration
	topology model.Topology
	// External services
	rabbitContainer rabbit.Container
	limiter         limiter
	repeater        repeater
	mongodb         *mongo.MongoDb
	metrics         metrics.Interface
	counter         counter
	nodes           map[string]*node
}

func (b *Bridge) Run(ctx context.Context) {
	b.timeout = b.topology.Timeout
	if b.timeout <= 0 {
		b.timeout = defaultTimeout
	}

	b.start(ctx)
}

func (b *Bridge) Process(dto *model.ProcessMessage) bool {
	nodeId := dto.GetHeaderOrDefault(enum.Header_NodeId, "")
	worker, ok := b.nodes[nodeId]
	if !ok {
		log.Error().Err(fmt.Errorf("missing worker for node [%s]", nodeId))
		return false
	}

	return worker.process(dto)
}

func NewBridge(rabbitClient *rabbitmq.Client, mongodb *mongo.MongoDb, topology model.Topology) Bridge {
	rabbitContainer := rabbit.NewContainer(rabbitClient, topology)
	return Bridge{
		topology:        topology,
		rabbitContainer: rabbitContainer,
		limiter:         newLimiter(rabbitContainer),
		repeater:        newRepeater(rabbitContainer),
		mongodb:         mongodb,
		metrics:         metrics.Connect(config.Metrics.Dsn),
		counter:         newCounter(rabbitContainer),
		nodes:           map[string]*node{},
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
			worker := newNode(*shard.Node, b.topology.ID, b.topology.Name, b.rabbitContainer, wg, b.limiter, b.repeater, b.mongodb, b.metrics, b.counter)
			b.nodes[shard.Node.ID] = worker
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

		for _, consumer := range b.rabbitContainer.Consumers {
			consumer.Close()
		}

		// Awaits rabbitMq nodes to process remaining messages
		log.Debug().Msg("awaiting rabbitMq workers to finish processes...")
		wg.Wait()

		// b.rabbitContainer.ClosePublishers()
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
