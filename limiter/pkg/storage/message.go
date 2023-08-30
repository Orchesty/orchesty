package storage

import (
	"fmt"
	"strconv"
	"time"

	"github.com/streadway/amqp"
	"gopkg.in/mgo.v2/bson"

	"limiter/pkg/model"
)

// LimiterLimitRow header defines the key for getting limit
const LimiterLimitRow = "pf-limiter-key"

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
	GroupKey         string
	GroupTime        int
	GroupValue       int
	Message          amqp.Publishing
}

// NewMessage creates storage Message struct by converting amqp Delivery to Publishing message and adding limit info
func NewMessage(delivery *amqp.Delivery) (*Message, error) {
	groupKey := ""
	groupTime := 0
	groupValue := 0
	limitKey := ""
	limitTime := 0
	limitValue := 0

	limitRow, ok := delivery.Headers[LimiterLimitRow]
	if ok {
		row, err := model.ParseLimiterRow(limitRow.(string))
		if err != nil {
			return nil, fmt.Errorf("bad format in %s", LimiterLimitRow)
		}
		limitKey = row.Base.Key
		limitTime = row.Base.Interval
		limitValue = row.Base.Count

		if row.Group != nil {
			groupKey = row.Group.Key
			groupTime = row.Group.Interval
			groupValue = row.Group.Count
		}

	} else {
		key, ok := delivery.Headers[LimitKeyHeader]
		if !ok {
			return nil, fmt.Errorf("missing header %s", LimitKeyHeader)
		}
		limitKey = key.(string)

		lt, ok := delivery.Headers[LimitTimeHeader]
		if !ok {
			return nil, fmt.Errorf("missing header %s", LimitTimeHeader)
		}
		l, err := strconv.Atoi(lt.(string))
		if err != nil {
			return nil, fmt.Errorf("failed limitTime value %s => %v", lt, err)
		}
		limitTime = l

		lv, ok := delivery.Headers[LimitValueHeader]
		if !ok {
			return nil, fmt.Errorf("missing header %s", LimitValueHeader)
		}
		v, err := strconv.Atoi(lv.(string))
		if err != nil {
			return nil, fmt.Errorf("failed limitValue value %s => %v", lv, err)
		}
		limitValue = v
	}

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

	return &Message{"", time.Now(), limitKey, limitTime, limitValue, exchange.(string), routingKey.(string), groupKey, groupTime, groupValue, innerMsg}, nil
}
