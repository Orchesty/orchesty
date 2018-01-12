package rabbitmq

import (
	"github.com/streadway/amqp"
	"log"
	"fmt"
)

type Publisher interface {
	Publish(msg amqp.Publishing)
}

type publisher struct {
	rabbitMq   RabbitMq
	channel    *amqp.Channel
	routingKey string
	Exchange   string
	Mandatory  bool
	Immediate  bool
}

func (p *publisher) Publish(msg amqp.Publishing) {

	if p.channel == nil {
		p.channel = p.rabbitMq.createChannel()
	}

	err := p.channel.Publish(p.Exchange, p.routingKey, p.Mandatory, p.Immediate, msg)

	if err != nil {
		log.Fatalln(fmt.Sprintf("Rabbit MQ publish error: %s", err))
	}

	log.Println("Rabbit MQ publish message")
}

func NewPublisher(r RabbitMq, routingKey string) (p Publisher) {
	return &publisher{rabbitMq: r, routingKey: routingKey}
}
