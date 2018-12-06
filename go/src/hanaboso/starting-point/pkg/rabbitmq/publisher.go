package rabbitmq

import (
	"fmt"
	"github.com/streadway/amqp"

	log "github.com/sirupsen/logrus"
)

// Publisher represents publisher
type Publisher interface {
	Publish(msg amqp.Publishing, routingKey string)
	clearChannels()
}

type publisher struct {
	connection Connection
	mandatory  bool
	immediate  bool
	log        *log.Logger
}

func (p *publisher) Publish(msg amqp.Publishing, routingKey string) {
	err := p.getChannel("").Publish("", routingKey, p.mandatory, p.immediate, msg)
	if err != nil {
		p.log.Error(fmt.Sprintf("Rabbit MQ publish error: %+v", err))

		v := <-p.connection.GetRestartChan()

		if v == true {
			p.Publish(msg, routingKey)
		}
	}

	p.log.Info(fmt.Sprintf("Rabbit MQ publish message with routing key '%s'", routingKey))
}

func (p *publisher) getChannel(name string) *amqp.Channel {
	return p.connection.GetChannel(name)
}

func (p *publisher) clearChannels() {
	p.connection.ClearChannels()
}

// NewPublisher construct
func NewPublisher(conn Connection, log *log.Logger) (p Publisher) {
	return &publisher{connection: conn, log: log}
}
