package storage

import (
	"encoding/json"
	"fmt"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"strconv"
	"time"

	amqp "github.com/rabbitmq/amqp091-go"

	"limiter/pkg/model"
)

// LimiterLimitRow header defines the key for getting limit
const LimiterLimitRow = "limiter-key"

// LimitKeyHeader header defines the key by which the requests are limited
const LimitKeyHeader = "limit-key"

// LimitTimeHeader header defines time
const LimitTimeHeader = "limit-time"

// LimitValueHeader header defines the value
const LimitValueHeader = "limit-value"

// ReturnExchangeHeader header defines the exchange where message should be routed back
const ReturnExchangeHeader = "limit-return-exchange"

// ReturnRoutingKeyHeader header defines the routing key to be used when message is routed back
const ReturnRoutingKeyHeader = "limit-return-routing-key"

// Message represents RabbitMQ message
type Message struct {
	ID               primitive.ObjectID `bson:"_id,omitempty"`
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

	var processDto model.ProcessDto
	_ = json.Unmarshal(delivery.Body, &processDto)

	limitRow, ok := processDto.Headers[LimiterLimitRow]
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
		key, ok := processDto.Headers[LimitKeyHeader]
		if !ok {
			return nil, fmt.Errorf("missing header %s", LimitKeyHeader)
		}
		limitKey = key.(string)

		lt, ok := processDto.Headers[LimitTimeHeader]
		if !ok {
			return nil, fmt.Errorf("missing header %s", LimitTimeHeader)
		}
		l, err := strconv.Atoi(lt.(string))
		if err != nil {
			return nil, fmt.Errorf("failed limitTime value %s => %v", lt, err)
		}
		limitTime = l

		lv, ok := processDto.Headers[LimitValueHeader]
		if !ok {
			return nil, fmt.Errorf("missing header %s", LimitValueHeader)
		}
		v, err := strconv.Atoi(lv.(string))
		if err != nil {
			return nil, fmt.Errorf("failed limitValue value %s => %v", lv, err)
		}
		limitValue = v
	}

	exchange, ok := processDto.Headers[ReturnExchangeHeader]
	if !ok || exchange == "" {
		return nil, fmt.Errorf("missing or empty header %s", ReturnExchangeHeader)
	}

	routingKey, ok := processDto.Headers[ReturnRoutingKeyHeader]
	if !ok || routingKey == "" {
		return nil, fmt.Errorf("missing or empty header %s", ReturnRoutingKeyHeader)
	}

	delete(processDto.Headers, ReturnExchangeHeader)
	delete(processDto.Headers, ReturnRoutingKeyHeader)

	newBody, _ := json.Marshal(processDto)
	headers := delivery.Headers
	headers["published-timestamp"] = time.Now().UnixNano() / 1_000_000

	innerMsg := amqp.Publishing{
		Headers:     headers,
		Body:        newBody,
		ContentType: delivery.ContentType,
		Priority:    delivery.Priority,
		ReplyTo:     delivery.ReplyTo,
		Type:        delivery.Type,
	}

	return &Message{primitive.NilObjectID, time.Now(), limitKey, limitTime, limitValue, exchange.(string), routingKey.(string), groupKey, groupTime, groupValue, innerMsg}, nil
}
