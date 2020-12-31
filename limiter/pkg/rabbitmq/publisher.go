package rabbitmq

import (
	"fmt"
	"github.com/streadway/amqp"
	"limiter/pkg/logger"
)

// Publisher represents the RabbitMQ publisher
type Publisher interface {
	Publish(msg amqp.Publishing)
	Stop()
	SetRoutingKey(string)
	SetExchange(string)
	SetMandatory(bool)
	SetImmediate(bool)
}

type publisher struct {
	connection Connection
	channelID  int
	routingKey string
	exchange   string
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

func (p *publisher) Publish(msg amqp.Publishing) {

	err := p.getChannel().Publish(p.exchange, p.routingKey, p.mandatory, p.immediate, msg)

	context := ctxFromPublishing(msg)

	if err != nil {
		context["error"] = err
		p.logger.Error(fmt.Sprintf("Rabbit MQ publish error: %s", err), context)

		v := <-p.connection.GetRestartChan()

		if v == true {
			p.Publish(msg)
		}
	}

	p.logger.Debug(fmt.Sprintf("Rabbit MQ publish message to exchange '%s' with routing key '%s'", p.exchange, p.routingKey), context)
}

func (p *publisher) Stop() {
	if p.channelID != -1 {
		p.connection.CloseChannel(p.channelID)
	}
	p.connection.Stop()
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

// NewPublisher returns newly created publisher instance
func NewPublisher(conn Connection, routingKey string, logger logger.Logger) (p Publisher) {
	return &publisher{connection: conn, routingKey: routingKey, channelID: -1, logger: logger}
}

func ctxFromPublishing(m amqp.Publishing) logger.Context {
	return logger.Context{"headers": m.Headers, "body": string(m.Body)}
}
