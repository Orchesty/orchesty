package rabbitmq

import (
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
)

type setupSvc struct {
	client
	shards []model.NodeShard
}

func setup(address string, shards []model.NodeShard) {
	s := setupSvc{
		shards: shards,
	}
	s.handleReconnect(&s, address)
}

func (s *setupSvc) connect() {
	ch, err := s.connection.Channel()
	if err != nil {
		log.Fatal().Err(err).Send()
	}
	for _, shard := range s.shards {
		queue := queue(shard)
		exchange := exchange(shard)
		// TODO declare hash exchange, review params
		if err = ch.ExchangeDeclare(exchange, "x-consistent-hash", true, false, false, false, nil); err != nil {
			log.Fatal().Err(err).Send()
		}
		if _, err = ch.QueueDeclare(queue, true, false, false, false, nil); err != nil {
			log.Fatal().Err(err).Send()
		}
		// TODO multiple routing keys
		if err = ch.QueueBind(queue, routingKey(shard), exchange, false, nil); err != nil {
			log.Fatal().Err(err).Send()
		}
	}
	// Gracefully close connection after setup to not to reconnect
	if err := s.connection.Close(); err != nil {
		log.Debug().Err(err).Send()
	}
}
