package rabbitmq

import (
	"github.com/streadway/amqp"
	"log"
	"fmt"
)

type Publisher interface {
	Publish(msg amqp.Publishing)
	SetRoutingKey(string)
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

func (p *publisher) getChannel() (*amqp.Channel) {
	if p.channelId == -1 {
		p.channelId = p.connection.CreateChannel()
	}

	return p.connection.GetChannel(p.channelId)
}

func (p *publisher) Publish(msg amqp.Publishing) {

	err := p.getChannel().Publish(p.exchange, p.routingKey, p.mandatory, p.immediate, msg)

	if err != nil {
		log.Println(fmt.Sprintf("Rabbit MQ publish error: %s", err))

		v := <-p.connection.GetRestartChan()

		if v == true {
			p.Publish(msg)
		}
	}

	log.Println(fmt.Sprintf("Rabbit MQ publish message to exchange '%s' with routing key '%s'", p.exchange, p.routingKey))
}

func (p *publisher) SetRoutingKey(k string) {
	p.routingKey = k
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
