package rabbitmq

import (
	"fmt"

	logger "github.com/sirupsen/logrus"
	"github.com/streadway/amqp"
)

// Publisher represents publisher
type Publisher interface {
	Publish(msg amqp.Publishing, exchange string, routingKey string)
	Stop()
	SetMandatory(bool)
	SetImmediate(bool)
}

type publisher struct {
	connection Connection
	channelID  int
	mandatory  bool
	immediate  bool
	logger     logger.Logger
}

func (p *publisher) getChannel() *amqp.Channel {
	if p.channelID == -1 {
		p.channelID = p.connection.CreateChannel()
	}

	return p.connection.GetChannel(p.channelID)
}

func (p *publisher) Publish(msg amqp.Publishing, exchange string, routingKey string) {

	err := p.getChannel().Publish(exchange, routingKey, p.mandatory, p.immediate, msg)

	if err != nil {
		p.logger.Error(fmt.Sprintf("Rabbit MQ publish error: %s", err))

		v := <-p.connection.GetRestartChan()

		if v == true {
			p.Publish(msg, exchange, routingKey)
		}
	}

	p.logger.Info(fmt.Sprintf("Rabbit MQ publish message to exchange '%s' with routing key '%s'", exchange, routingKey))
}

func (p *publisher) Stop() {
	if p.channelID != -1 {
		p.connection.CloseChannel(p.channelID)
	}
	p.connection.Stop()
}

func (p *publisher) SetMandatory(m bool) {
	p.mandatory = m
}

func (p *publisher) SetImmediate(i bool) {
	p.immediate = i
}

// NewPublisher construct
func NewPublisher(conn Connection, logger logger.Logger) (p Publisher) {
	return &publisher{connection: conn, channelID: -1, logger: logger}
}
