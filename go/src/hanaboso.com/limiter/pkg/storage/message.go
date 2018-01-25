package storage

import (
	"github.com/streadway/amqp"
	"fmt"
	"strconv"
	"gopkg.in/mgo.v2/bson"
	"time"
)

const LimitKeyHeader = "pf-limit-key"
const LimitTimeHeader = "pf-limit-time"
const LimitValueHeader = "pf-limit-value"
const ReturnExchangeHeader = "pf-limit-return-exchange"
const ReturnRoutingKeyHeader = "pf-limit-return-routing-key"

const SystemKeyHeader = "pf-system-key"
const GuidHeader = "pf-guid"
const TokenHeader = "token-guid"

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
	}

	return &Message{"", time.Now(), key.(string), lt, lv, exchange.(string), routingKey.(string), innerMsg}, nil
}
