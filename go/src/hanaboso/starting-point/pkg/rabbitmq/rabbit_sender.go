package rabbitmq

import (
	"encoding/json"
	logger "github.com/sirupsen/logrus"
	"github.com/streadway/amqp"
	"starting-point/pkg/headers"
)

// RabbitSender represents rabbitSender
type RabbitSender interface {
	SndMessage(Queue, amqp.Publishing)
}

type rabbitSender struct {
	publisher  Publisher
	connection Connection
}

type counterBody struct {
	result resultBody
	route  routeBody
}

type resultBody struct {
	code    int
	message string
}

type routeBody struct {
	following  int
	multiplier int
}

func (r *rabbitSender) SndMessage(q Queue, msg amqp.Publishing) {
	initCounterProcess(r)
	r.connection.Declare(q)
	r.publisher.Publish(msg, "", q.Name)
}

func initCounterProcess(r *rabbitSender) {
	body, err := json.Marshal(
		counterBody{
			result: resultBody{0, "Starting point started process"},
			route:  routeBody{1, 1},
		})

	topology := headers.Topology{ID: "ida", Name: "namea"}

	builder := headers.NewBuilder("1")
	h := builder.BldCounterHeaders(topology)

	if err != nil {
		logger.Error("spadlo to máchale")
	}

	q := Queue{Name: "pipes.multi-counter", Durable: true} // TODO: dát do env
	msg := amqp.Publishing{Body: body, Headers: h}

	r.connection.Declare(q)
	r.publisher.Publish(msg, "", q.Name)
}

// NewRabbitSender construct
func NewRabbitSender() RabbitSender {
	l := logger.New()
	conn := NewConnection("rabbitmq", 5672, "guest", "guest", *l)
	conn.Connect()
	publisher := NewPublisher(conn, *l)

	return &rabbitSender{publisher: publisher, connection: conn}
}
