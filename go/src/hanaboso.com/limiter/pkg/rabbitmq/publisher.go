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
	connection Connection
	channelId  int
	routingKey string
	exchange   string
	mandatory  bool
	immediate  bool
}

func (p *publisher) Publish(msg amqp.Publishing) {

	if p.channelId == -1 {
		p.channelId = p.connection.CreateChannel()
	}

	err := p.connection.GetChannel(p.channelId).Publish(p.exchange, p.routingKey, p.mandatory, p.immediate, msg)

	if err != nil {
		log.Println(fmt.Sprintf("Rabbit MQ publish error: %s", err))

		v := <-p.connection.GetRestartChan()

		if v == true {
			p.Publish(msg)
		}
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

func NewPublisher(conn Connection, routingKey string) (p Publisher) {
	return &publisher{connection: conn, routingKey: routingKey, channelId: -1}
}
