package rabbitmq

import (
	"fmt"
	log "github.com/sirupsen/logrus"
	"github.com/streadway/amqp"
)

// Publisher represents publisher
type Publisher interface {
	Publish(msg amqp.Publishing, exchange string, routingKey string)
}

type publisher struct {
	connection Connection
	mandatory  bool
	immediate  bool
	log        *log.Logger
}

func (p *publisher) Publish(msg amqp.Publishing, exchange string, routingKey string) {
	// TODO: todle je špatně :(
	err := p.getChannel(exchange).Publish(exchange, routingKey, p.mandatory, p.immediate, msg)
	if err != nil {
		p.log.Error(fmt.Sprintf("Rabbit MQ publish error: %+v", err))

		v := <-p.connection.GetRestartChan()

		if v == true {
			p.Publish(msg, exchange, routingKey)
		}
	}

	p.log.Info(fmt.Sprintf("Rabbit MQ publish message to exchange '%s' with routing key '%s'", exchange, routingKey))
}

func (p *publisher) getChannel(name string) *amqp.Channel {
	return p.connection.GetChannel(name)
}

// NewPublisher construct
func NewPublisher(conn Connection, log *log.Logger) (p Publisher) {
	return &publisher{connection: conn, log: log}
}
