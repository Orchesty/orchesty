package rabbitmq

import (
	"github.com/streadway/amqp"
	"log"
	"fmt"
)

type Publisher interface {
	Publish(msg amqp.Publishing)
	SetExchange(string)
	SetMandatory(bool)
	SetImmediate(bool)
}

type publisher struct {
	rabbitMq   RabbitMq
	channelId  int
	routingKey string
	exchange   string
	mandatory  bool
	immediate  bool
}

func (p *publisher) Publish(msg amqp.Publishing) {

	if p.channelId == -1 {
		p.channelId = p.rabbitMq.CreateChannel()
	}

	err := p.rabbitMq.GetChannel(p.channelId).Publish(p.exchange, p.routingKey, p.mandatory, p.immediate, msg)

	if err != nil {
		log.Println(fmt.Sprintf("Rabbit MQ publish error: %s", err))
		p.rabbitMq.Reconnect()
		p.Publish(msg)
	}

	log.Println("Rabbit MQ publish message")
}

func (p *publisher) SetExchange(e string) {
	p.exchange = e
}

func (p *publisher) SetMandatory(m bool) {
	p.mandatory = m
}

func (p *publisher) SetImmediate(i bool) {
	p.immediate = i
}

func NewPublisher(r RabbitMq, routingKey string) (p Publisher) {
	return &publisher{rabbitMq: r, routingKey: routingKey, channelId: -1}
}
