package service

import (
	"encoding/json"
	"fmt"
	metrics "github.com/hanaboso/go-metrics/pkg"
	amqp "github.com/rabbitmq/amqp091-go"
	"net/http"
	"starting-point/pkg/config"
	"starting-point/pkg/rabbitmq"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"
	"strings"

	log "github.com/sirupsen/logrus"
)

// Rabbit represents rabbit
type Rabbit interface {
	SendMessage(r *http.Request, topology storage.Topology, init map[string]float64)
	DisconnectRabbit()
	ClearChannels()
	IsMetricsConnected() bool
}

// RabbitDefault interprets Rabbit
type RabbitDefault struct {
	publisher  rabbitmq.Publisher
	connection rabbitmq.Connection
	builder    utils.HeaderBuilder
	metrics    metrics.Interface
}

// ProcessMessage  body structures
// ---------------------------------------------------------------------------------------------

// RabbitMq describe
var RabbitMq Rabbit

// ConnectToRabbit init
func ConnectToRabbit() {
	RabbitMq = NewRabbit()
}

type MessageDto struct {
	Body    string                 `json:"body"`
	Headers map[string]interface{} `json:"headers"`
}

func (r *RabbitDefault) SendMessage(
	request *http.Request,
	topology storage.Topology,
	init map[string]float64) {
	// Create ProcessMessage headers
	h, c, d, t := r.builder.BuildHeaders(topology)

	user := ""
	if user = request.Header.Get(utils.UserID); user != "" {
		h[utils.UserID] = user
	}

	limitHeader, err := GetApplicationLimits(user, topology)
	if err != nil {
		log.Error(fmt.Sprintf("Cannot fetch sdk's limits: %+v", err))
		return
	}
	h[utils.LimitKey] = limitHeader

	apps := make([]string, len(topology.Applications))
	for i, app := range topology.Applications {
		apps[i] = app.Key
	}
	h[utils.Applications] = strings.Join(apps, ";")

	dto := MessageDto{
		Body:    string(utils.GetBodyFromStream(request)),
		Headers: h,
	}
	marshaled, _ := json.Marshal(dto)

	m := amqp.Publishing{Body: marshaled, Headers: map[string]interface{}{
		utils.PublishedTimeStamp: utils.Now(),
	}, ContentType: c, DeliveryMode: d, Timestamp: t}
	corrID := h[utils.CorrelationID]

	r.publisher.Publish(m, topology.Node.Exchange(), "1")

	// Send Metrics
	if err := r.metrics.Send(config.Config.Metrics.Measurement, utils.GetTags(topology, corrID.(string)), utils.GetFields(init)); err != nil {
		log.Error(fmt.Sprintf("Metrics error: %+v", err))
	}
}

// DisconnectRabbit disconnects RabbitMQ
func (r *RabbitDefault) DisconnectRabbit() {
	r.connection.Disconnect()
	r.metrics.Disconnect()
}

// ClearChannels clears channels form connection
func (r *RabbitDefault) ClearChannels() {
	r.connection.ClearChannels()
}

// IsMetricsConnected checks metrics connection status
func (r *RabbitDefault) IsMetricsConnected() bool {
	return r.metrics.IsConnected()
}

// NewRabbit construct
func NewRabbit() Rabbit {
	conn := rabbitmq.NewConnection(
		config.Config.RabbitMQ.Hostname,
		int(config.Config.RabbitMQ.Port),
		config.Config.RabbitMQ.Vhost,
		config.Config.RabbitMQ.Username,
		config.Config.RabbitMQ.Password,
		config.Config.Logger)
	conn.Connect()
	publisher := rabbitmq.NewPublisher(conn, config.Config.Logger)
	builder := utils.NewHeaderBuilder(config.Config.RabbitMQ.DeliveryMode)

	return &RabbitDefault{
		publisher:  publisher,
		connection: conn,
		builder:    builder,
		metrics:    metrics.Connect(config.Config.Metrics.Dsn),
	}
}
