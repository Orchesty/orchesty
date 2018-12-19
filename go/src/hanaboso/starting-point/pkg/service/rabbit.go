package service

import (
	"encoding/json"
	"fmt"
	"github.com/streadway/amqp"
	"net/http"
	"starting-point/pkg/config"
	"starting-point/pkg/influx"
	"starting-point/pkg/rabbitmq"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"

	log "github.com/sirupsen/logrus"
)

// Rabbit represents rabbit
type Rabbit interface {
	SndMessage(r *http.Request, topology storage.Topology, init map[string]float64, isHuman bool, isStop bool)
	DisconnectRabbit()
	ClearChannels()
}

// RabbitDefault interprets Rabbit
type RabbitDefault struct {
	publisher  rabbitmq.Publisher
	connection rabbitmq.Connection
	builder    utils.HeaderBuilder
}

// ProcessMessage  body structures
// ---------------------------------------------------------------------------------------------

// CounterBody interprets body
type CounterBody struct {
	Result ResultBody `json:"result"`
	Route  RouteBody  `json:"route"`
}

// ResultBody interprets result
type ResultBody struct {
	Code    int    `json:"code"`
	Message string `json:"message"`
}

// RouteBody interprets route
type RouteBody struct {
	Following  int `json:"following"`
	Multiplier int `json:"multiplier"`
}

// ---------------------------------------------------------------------------------------------

// RabbitMq describe
var RabbitMq Rabbit

// ConnectToRabbit init
func ConnectToRabbit() {
	RabbitMq = NewRabbit()
}

// SndMessage sends message to RabbitMQ
func (r *RabbitDefault) SndMessage(
	request *http.Request,
	topology storage.Topology,
	init map[string]float64,
	isHuman bool,
	isStop bool) {

	// Create ProcessMessage headers
	h, c, d, t := r.builder.BldHeaders(topology, request.Header, isHuman, isStop)

	// Create Queue & Message
	q := rabbitmq.GetProcessQueue(topology)
	m := amqp.Publishing{Body: utils.GetBodyFromStream(request), Headers: h, ContentType: c, DeliveryMode: d, Timestamp: t}

	// Init Counter
	r.initCounterProcess(request.Header, topology)

	// Declare Queue & Publish Message
	r.connection.Declare(q)
	r.publisher.Publish(m, q.Name)

	// Send Metrics
	corrID := m.Headers[utils.CorrelationID]
	influx.SendMetrics(influx.GetTags(topology, corrID.(string)), influx.GetFields(init))
}

// DisconnectRabbit disconnects RabbitMQ
func (r *RabbitDefault) DisconnectRabbit() {
	r.connection.Disconnect()
}

// ClearChannels clears channels form connection
func (r *RabbitDefault) ClearChannels() {
	r.connection.ClearChannels()
}

func (r *RabbitDefault) initCounterProcess(httpHeaders http.Header, topology storage.Topology) {
	// Create ProcessMessage body
	body, err := json.Marshal(
		CounterBody{
			Result: ResultBody{0, "Starting point started process"},
			Route:  RouteBody{1, 1},
		})

	if err != nil {
		log.Error(fmt.Sprintf("Json marshal error: %+v", err))
	}

	// Create ProcessMessage headers
	h, c, d, t := r.builder.BldCounterHeaders(topology, httpHeaders)

	// Create & Publish Message
	msg := amqp.Publishing{Body: body, Headers: h, ContentType: c, DeliveryMode: d, Timestamp: t}
	r.publisher.Publish(msg, config.Config.RabbitMQ.CounterQueueName)
}

// NewRabbit construct
func NewRabbit() Rabbit {
	conn := rabbitmq.NewConnection(
		config.Config.RabbitMQ.Hostname,
		int(config.Config.RabbitMQ.Port),
		config.Config.RabbitMQ.Password,
		config.Config.RabbitMQ.Username,
		config.Config.Logger)
	conn.Connect()
	publisher := rabbitmq.NewPublisher(conn, config.Config.Logger)
	builder := utils.NewHeaderBuilder(config.Config.RabbitMQ.DeliveryMode)

	// Declare Process-Counter queue
	conn.Declare(rabbitmq.GetProcessCounterQueue())
	conn.CloseChannel(config.Config.RabbitMQ.CounterQueueName)

	return &RabbitDefault{publisher: publisher, connection: conn, builder: builder}
}
