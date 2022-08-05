package rabbitmq

import (
	"context"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
	"github.com/streadway/amqp"
	"time"
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
		rk := routingKey(shard)

		declareExchange(ch, exchange)
		declareQueue(ch, queue)
		if err := bind(ch, queue, exchange, rk); err != nil {
			log.Fatal().Err(err).Send()
		}
	}
	// Gracefully close connection after setup to not to reconnect
	if err := s.connection.Close(); err != nil {
		log.Debug().Err(err).Send()
	}
}

func declareExchange(channel *amqp.Channel, exchange string) {
	// Because go lib is bullshit and has not error or timeout on missing plugin -> only hangs up
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	go func() {
		<-ctx.Done()
		if err := ctx.Err(); err == context.DeadlineExceeded {
			log.Fatal().Err(err).Send()
		}
	}()

	if err := channel.ExchangeDeclare(exchange, "x-consistent-hash", true, false, false, false, nil); err != nil {
		log.Fatal().Err(err).Send()
	}
	cancel()
}

func declareQueue(channel *amqp.Channel, queueName string) {
	if _, err := channel.QueueDeclare(queueName, true, false, false, false, nil); err != nil {
		log.Fatal().Err(err).Send()
	}
}

func bind(channel *amqp.Channel, queue, exchange, routingKey string) error {
	return channel.QueueBind(queue, routingKey, exchange, false, nil)
}
