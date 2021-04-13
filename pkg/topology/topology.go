package topology

import (
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/rabbitmq"
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
		parser: jsonParserV1{},
	}
}

// Parse topology.json, setup rabbitmq and connect subscribers
func (t TopologySvc) Parse(path string) (model.Topology, error) {
	topology, err := t.parser.getTopology(path)
	if err != nil {
		return model.Topology{}, err
	}

	// Setup rabbitmq on one arbitrary address
	t.rabbitMQ.Setup(topology.Shards[0].RabbitMQDSN, topology.Shards)

	rabbitMQShards := make(map[string][]model.NodeShard)
	for _, shard := range topology.Shards {
		if shard.Node.Worker.ServiceType() == enum.ServiceType_Rabbit {
			if _, ok := rabbitMQShards[shard.RabbitMQDSN]; !ok {
				rabbitMQShards[shard.RabbitMQDSN] = make([]model.NodeShard, 0)
			}
			rabbitMQShards[shard.RabbitMQDSN] = append(rabbitMQShards[shard.RabbitMQDSN], shard)
		}
	}

	for DSN, shards := range rabbitMQShards {
		// Setup on different addresses is just convenient. It is not necessary - all shards can be setup on one address.
		t.rabbitMQ.Setup(DSN, shards)
		t.rabbitMQ.ConnectSubscribers(shards)
	}

	return topology, nil
}
