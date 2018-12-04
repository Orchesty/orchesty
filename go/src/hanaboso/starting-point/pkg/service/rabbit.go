package service

import (
	"encoding/json"
	"fmt"
	"github.com/streadway/amqp"
	"net/http"
	"starting-point/pkg/config"
	"starting-point/pkg/rabbitmq"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"

	log "github.com/sirupsen/logrus"
)

// Rabbit represents rabbit
type Rabbit interface {
	SndMessage(*http.Request, storage.Topology)
	DisconnectRabbit()
}

type rabbit struct {
	publisher  rabbitmq.Publisher
	connection rabbitmq.Connection
	builder    utils.HeaderBuilder
}

// ProcessMessage  body structures
// ---------------------------------------------------------------------------------------------
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

// ---------------------------------------------------------------------------------------------

// RabbitMq describe
var RabbitMq Rabbit

// ConnectToRabbit init
func ConnectToRabbit() {
	RabbitMq = NewRabbit()
}

func (r *rabbit) SndMessage(request *http.Request, topology storage.Topology) {

	// Create Queue & Message
	queueName := utils.GenerateTplgName(topology)
	q := rabbitmq.Queue{Name: queueName, Durable: true, AutoDelete: false}
	m := amqp.Publishing{Body: utils.GetBodyFromStream(request), Headers: r.builder.BldProcessHeaders(topology, request.Header)}

	// Init Counter
	r.initCounterProcess(request.Header, topology)

	// Declare Queue & Publish Message
	r.connection.Declare(q)
	r.publisher.Publish(m, "", q.Name)
}

func (r *rabbit) DisconnectRabbit() {
	r.connection.Disconnect()
}

func (r *rabbit) initCounterProcess(httpHeaders http.Header, topology storage.Topology) {
	// Create ProcessMessage body
	body, err := json.Marshal(
		counterBody{
			result: resultBody{0, "Starting point started process"},
			route:  routeBody{1, 1},
		})

	if err != nil {
		log.Error(fmt.Sprintf("Json marshal error: %+v", err))
	}

	// Create ProcessMessage headers
	h := r.builder.BldCounterHeaders(topology, httpHeaders)

	// Create Queue & Message
	q := rabbitmq.Queue{Name: config.Config.RabbitMQ.CounterQueueName, Durable: config.Config.RabbitMQ.CounterQueueDurable}
	msg := amqp.Publishing{Body: body, Headers: h}

	// Declare Queue & Publish Message
	r.connection.Declare(q)
	r.publisher.Publish(msg, "", q.Name)
}

// NewRabbit construct
func NewRabbit() Rabbit {
	conn := rabbitmq.NewConnection(
		config.Config.RabbitMQ.Hostname,
		5672,
		config.Config.RabbitMQ.Password,
		config.Config.RabbitMQ.Username,
		config.Config.Logger)
	conn.Connect()
	publisher := rabbitmq.NewPublisher(conn, config.Config.Logger)
	builder := utils.NewHeaderBuilder(config.Config.RabbitMQ.DeliveryMode)

	return &rabbit{publisher: publisher, connection: conn, builder: builder}
}
