package topology

import (
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
)

type parser interface {
	// GetTopology returns topology model with enabled shard nodes
	getTopology(path string) (model.Topology, error)
}

type TopologySvc struct {
	rabbitMQ *rabbitmq.Client
	parser   parser
}

func NewTopologySvc() TopologySvc {
	return TopologySvc{
		// TODO decide what parser to use
		parser: jsonParserV2{},
	}
}

// Parse topology.json
func (t TopologySvc) Parse(path string) (model.Topology, error) {
	topology, err := t.parser.getTopology(path)
	if err != nil {
		return model.Topology{}, err
	}
	log.Debug().Str(enum.LogHeader_TopologyId, topology.ID).
		Interface(enum.LogHeader_Data, model.LogData{"shards": len(topology.Shards)}).
		Msg("successfully parsed topology")

	return topology, nil
}
