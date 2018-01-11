package rabbitmq

import (
	"github.com/streadway/amqp"
	"log"
	"fmt"
)

type Publisher struct {
	RabbitMq   RabbitMq
	channel    *amqp.Channel
	Exchange   string
	RoutingKey string
	Mandatory  bool
	Immediate  bool
}

func (p *Publisher) Publish(msg amqp.Publishing) {

	if p.channel == nil {
		p.channel = p.RabbitMq.createChannel()
	}

	err := p.channel.Publish(p.Exchange, p.RoutingKey, p.Mandatory, p.Immediate, msg)

	if err != nil {
		log.Fatalln(fmt.Sprintf("Rabbit MQ publish error: %s", err))
	}
}
