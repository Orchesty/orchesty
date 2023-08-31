package service

import (
	"encoding/json"
	"fmt"
	log "github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-log/pkg/zap"
	metrics "github.com/hanaboso/go-metrics/pkg"
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	amqp "github.com/rabbitmq/amqp091-go"
	"net/http"
	"starting-point/pkg/config"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"
	"strings"
)

type Rabbit interface {
	SendMessage(r *http.Request, topology storage.Topology, init map[string]float64)
	Disconnect()
	IsMetricsConnected() bool
}

type RabbitSvc struct {
	publisher *rabbitmq.Publisher
	client    *rabbitmq.Client
	builder   utils.HeaderBuilder
	metrics   metrics.Interface
	logger    log.Logger
}

type MessageDto struct {
	Body    string                 `json:"body"`
	Headers map[string]interface{} `json:"headers"`
}

// ProcessMessage  body structures
// ---------------------------------------------------------------------------------------------

var RabbitMq Rabbit

func ConnectToRabbit() {
	rabbitClient := rabbitmq.NewClient(config.RabbitMQ.Dsn, zap.NewLogger(), true)

	RabbitMq = RabbitSvc{
		client:    rabbitClient,
		publisher: rabbitClient.NewPublisher("", ""),
		builder:   utils.NewHeaderBuilder(config.RabbitMQ.DeliveryMode),
		metrics:   metrics.Connect(config.Metrics.Dsn),
		logger:    config.Logger,
	}
}

func (this RabbitSvc) SendMessage(
	request *http.Request,
	topology storage.Topology,
	init map[string]float64) {
	// Create ProcessMessage headers
	h, c, d, t := this.builder.BuildHeaders(topology)

	user := ""
	if user = request.Header.Get(utils.UserID); user != "" {
		h[utils.UserID] = user
	}

	limitHeader, err := GetApplicationLimits(user, topology)
	if err != nil {
		config.Logger.Error(fmt.Errorf("cannot fetch sdk's limits: %+v, %v", err, limitHeader))
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

	err = this.publisher.PublishExchangeRoutingKey(m, topology.Node.Exchange(), "1")
	if err != nil {
		this.RefreshExchange(topology.Node.Queue(), topology.Node.Exchange(), "1")
		err = this.publisher.PublishExchangeRoutingKey(m, topology.Node.Exchange(), "1")
		if err != nil {
			this.logger.Error(err)
		}
	}

	// Send Metrics
	if err := this.metrics.Send(config.Metrics.Measurement, utils.GetTags(topology, corrID.(string)), utils.GetFields(init)); err != nil {
		this.logger.Error(fmt.Errorf("metrics error: %+v", err))
	}
}

func (this RabbitSvc) Disconnect() {
	this.client.Close()
	this.metrics.Disconnect()
}

func (this RabbitSvc) IsMetricsConnected() bool {
	return this.metrics.IsConnected()
}

func (this RabbitSvc) RefreshExchange(queue, exchange, routingKey string) {
	err := this.client.DeclareExchange(rabbitmq.Exchange{
		Name:    exchange,
		Kind:    "x-consistent-hash",
		Options: rabbitmq.DefaultExchangeOptions,
		Bindings: []rabbitmq.BindOptions{
			{
				Queue:  queue,
				Key:    routingKey,
				NoWait: false,
				Args:   nil,
			},
		},
	})

	if err != nil {
		this.logger.Debug(fmt.Sprintf("redeclare exchange: %v", err))
	}
}
