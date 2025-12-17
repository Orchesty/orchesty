package rabbit

import (
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/utils/intx"
	"github.com/rs/zerolog/log"
)

type Container struct {
	Consumers  map[string]*rabbitmq.Consumer
	Publishers map[string]types.Publisher
	Repeater   types.Publisher
	Counter    types.Publisher
	Limiter    types.Publisher
}

func NewContainer(client *rabbitmq.Client, topology model.Topology) Container {
	container := Container{
		Consumers:  map[string]*rabbitmq.Consumer{},
		Publishers: map[string]types.Publisher{},
	}
	container.initServiceQueues(client)
	container.initNodes(client, topology)
	if err := client.InitializeQueuesExchanges(); err != nil {
		log.Fatal().Err(err).Send()
	}

	return container
}

func (this *Container) initServiceQueues(client *rabbitmq.Client) {
	for _, queue := range []string{enum.Queue_Counter, enum.Queue_Repeater, enum.Queue_Limiter} {
		client.AddQueue(rabbitmq.Queue{
			Name: queue,
			Options: rabbitmq.QueueOptions{
				Durable: true,
				Args: map[string]interface{}{
					"x-queue-type": "quorum",
				},
			},
		})
	}

	this.Limiter = client.NewPublisher("", enum.Queue_Limiter)
	this.Repeater = client.NewPublisher("", enum.Queue_Repeater)
	this.Counter = client.NewPublisher("", enum.Queue_Counter)
}

func (this *Container) initNodes(client *rabbitmq.Client, topology model.Topology) {
	for _, shard := range topology.Shards {
		queueName := Queue(shard)
		exchangeName := Exchange(shard)
		routingKey := RoutingKey(shard)

		client.AddQueue(rabbitmq.Queue{
			Name: queueName,
			Options: rabbitmq.QueueOptions{
				Durable: true,
				Args: map[string]interface{}{
					"x-queue-type": "quorum",
				},
			},
		})
		client.AddExchange(rabbitmq.Exchange{
			Name:    exchangeName,
			Kind:    "x-consistent-hash",
			Options: rabbitmq.DefaultExchangeOptions,
			Bindings: []rabbitmq.BindOptions{
				{
					Queue:  queueName,
					Key:    routingKey,
					NoWait: false,
					Args:   nil,
				},
			},
		})

		publisher := client.NewPublisher(exchangeName, routingKey)
		consumer := client.NewConsumer(queueName, intx.Max(shard.Node.Settings.Bridge.Prefetch, 1))
		this.Publishers[shard.Node.ID] = publisher
		this.Consumers[shard.Node.ID] = consumer
	}
}
