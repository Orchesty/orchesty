package rabbit

import (
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"github.com/rs/zerolog/log"
	"limiter/pkg/config"
	"limiter/pkg/model"
)

const (
	queue_Limiter  = "pipes.limiter"
	queue_Repeater = "pipes.repeater"
)

type RabbitSvc struct {
	client           *rabbitmq.Client
	LimiterConsumer  rabbitmq.IJsonConsumer[model.MessageDto]
	RepeaterConsumer rabbitmq.IJsonConsumer[model.MessageDto]
}

func NewRabbitSvc() RabbitSvc {
	client := rabbitmq.NewClient(config.RabbitMq.Dsn, config.Logger, true)
	options := rabbitmq.QueueOptions{
		Durable: true,
		Args: map[string]interface{}{
			"x-queue-type": "quorum",
		},
	}

	client.AddQueues([]rabbitmq.Queue{
		{
			Name:    queue_Limiter,
			Options: options,
		},
		{
			Name:    queue_Repeater,
			Options: options,
		},
	},
	)
	err := client.InitializeQueuesExchanges()
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	return RabbitSvc{
		client:           client,
		LimiterConsumer:  &rabbitmq.JsonConsumer[model.MessageDto]{Consumer: client.NewConsumer(queue_Limiter, 20)},
		RepeaterConsumer: &rabbitmq.JsonConsumer[model.MessageDto]{Consumer: client.NewConsumer(queue_Repeater, 20)},
	}
}
