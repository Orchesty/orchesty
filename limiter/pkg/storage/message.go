package storage

import (
	"fmt"
	"strconv"
	"time"

	"github.com/streadway/amqp"
	"gopkg.in/mgo.v2/bson"
)

// LimitKeyHeader header defines the key by which the requests are limited
const LimitKeyHeader = "pf-limit-key"

// LimitTimeHeader header defines time
const LimitTimeHeader = "pf-limit-time"

// LimitValueHeader header defines the value
const LimitValueHeader = "pf-limit-value"

// ReturnExchangeHeader header defines the exchange where message should be routed back
const ReturnExchangeHeader = "pf-limit-return-exchange"

// ReturnRoutingKeyHeader header defines the routing key to be used when message is routed back
const ReturnRoutingKeyHeader = "pf-limit-return-routing-key"

// SystemKeyHeader defines system key header name
const SystemKeyHeader = "pf-system-key"

// GUIDHeader defines guid key header name
const GUIDHeader = "pf-guid"

// TokenHeader defines token key header name
const TokenHeader = "token-guid"

// Message represents RabbitMQ message
type Message struct {
	ID               bson.ObjectId `bson:"_id,omitempty"`
	Created          time.Time
	LimitKey         string
	LimitTime        int
	LimitValue       int
	ReturnExchange   string
	ReturnRoutingKey string
	Message          amqp.Publishing
}

// NewMessage creates storage Message struct by converting amqp Delivery to Publishing message and adding limit info
func NewMessage(delivery *amqp.Delivery) (*Message, error) {

	key, ok := delivery.Headers[LimitKeyHeader]
	if !ok {
		return nil, fmt.Errorf("missing header %s", LimitKeyHeader)
	}

	limitTime, ok := delivery.Headers[LimitTimeHeader]
	if !ok {
		return nil, fmt.Errorf("missing header %s", LimitTimeHeader)
	}
	lt, _ := strconv.Atoi(limitTime.(string))

	limitValue, ok := delivery.Headers[LimitValueHeader]
	if !ok {
		return nil, fmt.Errorf("missing header %s", LimitValueHeader)
	}
	lv, _ := strconv.Atoi(limitValue.(string))

	exchange, ok := delivery.Headers[ReturnExchangeHeader]
	if !ok || exchange == "" {
		return nil, fmt.Errorf("missing or empty header %s", ReturnExchangeHeader)
	}

	routingKey, ok := delivery.Headers[ReturnRoutingKeyHeader]
	if !ok || routingKey == "" {
		return nil, fmt.Errorf("missing or empty header %s", ReturnRoutingKeyHeader)
	}

	delete(delivery.Headers, ReturnExchangeHeader)
	delete(delivery.Headers, ReturnRoutingKeyHeader)

	innerMsg := amqp.Publishing{
		Headers:     delivery.Headers,
		Body:        delivery.Body,
		ContentType: delivery.ContentType,
		Priority:    delivery.Priority,
		ReplyTo:     delivery.ReplyTo,
		Type:        delivery.Type,
	}

	return &Message{"", time.Now(), key.(string), lt, lv, exchange.(string), routingKey.(string), innerMsg}, nil
}
