package rabbitmq

import (
	"fmt"
	log "github.com/sirupsen/logrus"
	"github.com/streadway/amqp"
)

// Publisher represents publisher
type Publisher interface {
	Publish(amqp.Publishing, string)
	clearChannels()
}

type publisher struct {
	connection Connection
	mandatory  bool
	immediate  bool
	log        *log.Logger
}

func (p *publisher) Publish(msg amqp.Publishing, routingKey string) {
	chD := p.getChannel(routingKey)
	err := chD.Ch.Publish("", routingKey, p.mandatory, p.immediate, msg)
	if err != nil {
		p.log.Error(fmt.Sprintf("Rabbit MQ publish error: %+v. Try to recconect.", err))
		p.connection.Connect()

		if <-p.connection.GetRestartChan() {
			p.Publish(msg, routingKey)
			return
		}
	}

	p.log.Info(fmt.Sprintf("Rabbit MQ publish message with routing key '%s'", routingKey))

	go func() {
		if confirmed := <-chD.Confirm; !confirmed.Ack {
			p.log.Error(fmt.Sprintf("NonConfirm"))
			p.Publish(msg, routingKey)
		}
	}()
}

func (p *publisher) getChannel(name string) ChanData {
	return p.connection.GetChannel(name)
}

func (p *publisher) clearChannels() {
	p.connection.ClearChannels()
}

// NewPublisher construct
func NewPublisher(conn Connection, log *log.Logger) (p Publisher) {
	return &publisher{connection: conn, log: log}
}
