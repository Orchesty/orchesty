package topology

import (
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/rabbitmq"
	"github.com/rs/zerolog/log"
)

type parser interface {
	// GetTopology returns topology model with enabled shard nodes
	getTopology(path string) (model.Topology, error)
}

type TopologySvc struct {
	rabbitMQ *rabbitmq.RabbitMQ
	parser   parser
}

func NewTopologySvc(rabbitMQ *rabbitmq.RabbitMQ) TopologySvc {
	return TopologySvc{
		rabbitMQ: rabbitMQ,
		// TODO decide what parser to use
		parser: jsonParserV2{},
	}
}

// Parse topology.json, setup rabbitmq and connect subscribers
func (t TopologySvc) Parse(path string) (model.Topology, error) {
	topology, err := t.parser.getTopology(path)
	if err != nil {
		return model.Topology{}, err
	}
	log.Debug().Str(enum.LogHeader_TopologyId, topology.ID).
		Interface(enum.LogHeader_Data, model.LogData{"shards": len(topology.Shards)}).
		Msg("successfully parsed topology")

	rabbitMQShards := make(map[string][]model.NodeShard)
	for _, shard := range topology.Shards {
		if shard.Node.Worker.ServiceType() == enum.ServiceType_Rabbit {
			if _, ok := rabbitMQShards[shard.RabbitMQDSN]; !ok {
				rabbitMQShards[shard.RabbitMQDSN] = make([]model.NodeShard, 0)
			}
			rabbitMQShards[shard.RabbitMQDSN] = append(rabbitMQShards[shard.RabbitMQDSN], shard)
		}
	}

	someDsn := ""
	for dsn, shards := range rabbitMQShards {
		// Setup on different addresses is just convenient. It is not necessary - all shards can be setup on one address.
		t.rabbitMQ.Setup(dsn, shards)
		t.rabbitMQ.ConnectSubscribers(shards)
		// Here the setup on different addresses will spread load
		t.rabbitMQ.ConnectPublishers(shards)
		someDsn = dsn
	}
	t.rabbitMQ.SetupLimitRepeat(someDsn)

	return topology, nil
}
