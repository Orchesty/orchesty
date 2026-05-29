package rabbit

import (
	"bytes"
	"encoding/json"
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/model"
	amqp "github.com/rabbitmq/amqp091-go"
)

type RabbitService struct {
	Client *rabbitmq.Client
	Events *rabbitmq.Publisher
}

func NewRabbitService() RabbitService {
	queueName := "pipes.multi-counter"
	client := rabbitmq.NewClient(config.RabbitMq.Dsn, config.Log, true)
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
		Name:    "orchesty.events",
		Kind:    "topic",
		Options: rabbitmq.DefaultExchangeOptions,
	})

	if err := client.InitializeQueuesExchanges(); err != nil {
		panic(err)
	}

	events := client.NewPublisher("orchesty.events", "topology.failed")

	return RabbitService{
		Client: client,
		Events: events,
	}
}

func ParseMessage(msg amqp.Delivery) *model.ParsedMessage {
	var message model.ProcessMessage

	// Cannot use regular decoder due to process-started timestamp -> it converts int64 to float64
	d := json.NewDecoder(bytes.NewBuffer(msg.Body))
	d.UseNumber()
	if err := d.Decode(&message); err != nil {
		config.Log.Error(err)
		return &model.ParsedMessage{
			Tag: msg.DeliveryTag, // Ensure that even failed messages are acked
			Ok:  false,
		}
	}

	var body model.ProcessBody
	if err := json.Unmarshal([]byte(message.Body), &body); err != nil {
		config.Log.Error(err)
		return &model.ParsedMessage{
			Tag: msg.DeliveryTag, // Ensure that even failed messages are acked
			Ok:  false,
		}
	}
	message.ProcessBody = body

	return &model.ParsedMessage{
		Headers:        msg.Headers,
		Tag:            msg.DeliveryTag,
		ProcessMessage: message,
		Ok:             true,
	}
}
