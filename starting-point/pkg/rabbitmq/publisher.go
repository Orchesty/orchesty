package rabbitmq

import (
	"fmt"

	"github.com/streadway/amqp"

	log "github.com/sirupsen/logrus"
)

// Publisher represents publisher
type Publisher interface {
	Publish(message amqp.Publishing, exchange, routingKey string)
	clearChannels()
}

type publisher struct {
	connection Connection
	mandatory  bool
	immediate  bool
	log        *log.Logger
}

func (p *publisher) Publish(msg amqp.Publishing, exchange, routingKey string) {
	chD := p.getChannel(exchange)
	err := chD.Ch.Publish(exchange, routingKey, p.mandatory, p.immediate, msg)
	if err != nil {
		p.log.Error(fmt.Sprintf("Rabbit MQ publish error: %+v. Try to recconect.", err))
		p.connection.Connect()

		if <-p.connection.GetRestartChan() {
			p.Publish(msg, exchange, routingKey)
			return
		}
	}

	p.log.Info(fmt.Sprintf("Rabbit MQ publish message with exchange [%s] routingKey [%s]", exchange, routingKey))

	go func() {
		// TODO tady se může posrat confirm při předbíhání zpráv
		if confirmed := <-chD.Confirm; !confirmed.Ack {
			p.log.Error(fmt.Sprintf("NonConfirm"))
			p.Publish(msg, exchange, routingKey)
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
