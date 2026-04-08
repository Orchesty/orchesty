package rabbit

import (
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	amqp "github.com/rabbitmq/amqp091-go"
	"notifier/pkg/config"
)

type Rabbit interface {
	Consume() <-chan amqp.Delivery
	Close()
	IsConnected() bool
}

type rabbitSvc struct {
	client   *rabbitmq.Client
	consumer *rabbitmq.Consumer
}

const (
	ExchangeEvents = "orchesty.events"
	QueueNotifier  = "notifier.events"
	DLXEvents      = "orchesty.events.dlx"
)

var bindingKeys = []string{
	"topology.*",
}

func Connect() Rabbit {
	client := rabbitmq.NewClient(config.RabbitMQ.Dsn, config.Logger, true)

	bindings := make([]rabbitmq.BindOptions, len(bindingKeys))
	for i, key := range bindingKeys {
		bindings[i] = rabbitmq.BindOptions{
			Queue: QueueNotifier,
			Key:   key,
		}
	}

	client.AddQueue(rabbitmq.Queue{
		Name: QueueNotifier,
		Options: rabbitmq.QueueOptions{
			Durable: true,
			Args: amqp.Table{
				"x-queue-type":           "quorum",
				"x-dead-letter-exchange": DLXEvents,
			},
		},
	})

	client.AddExchange(rabbitmq.Exchange{
		Name:     ExchangeEvents,
		Kind:     "topic",
		Options:  rabbitmq.DefaultExchangeOptions,
		Bindings: bindings,
	})

	client.AddQueue(rabbitmq.Queue{
		Name: "notifier.events.dlx",
		Options: rabbitmq.QueueOptions{
			Durable: true,
			Args:    amqp.Table{"x-queue-type": "quorum"},
		},
	})

	client.AddExchange(rabbitmq.Exchange{
		Name:    DLXEvents,
		Kind:    "fanout",
		Options: rabbitmq.DefaultExchangeOptions,
		Bindings: []rabbitmq.BindOptions{
			{Queue: "notifier.events.dlx", Key: ""},
		},
	})

	if err := client.InitializeQueuesExchanges(); err != nil {
		config.Logger.Error(err)

		panic(err)
	}

	consumer := client.NewConsumer(QueueNotifier, 1)

	config.Logger.Info("RabbitMQ successfully connected!")

	return rabbitSvc{client, consumer}
}

func (r rabbitSvc) Consume() <-chan amqp.Delivery {
	return r.consumer.Consume(false)
}

func (r rabbitSvc) Close() {
	r.consumer.Close()
	r.client.Close()
}

func (r rabbitSvc) IsConnected() bool {
	conn := r.client.RawConnection()

	return conn != nil && !conn.IsClosed()
}
