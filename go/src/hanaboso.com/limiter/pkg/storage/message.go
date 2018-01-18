package storage

import (
	"github.com/streadway/amqp"
	"fmt"
	"strconv"
	"gopkg.in/mgo.v2/bson"
)

type Message struct {
	ID               bson.ObjectId `bson:"_id,omitempty"`
	LimitKey         string
	LimitTime        int
	LimitValue       int
	ReturnExchange   string
	ReturnRoutingKey string
	Message          amqp.Publishing
}

// NewMessage creates storage Message struct by converting amqp Delivery to Publishing message and adding limit info
func NewMessage(delivery *amqp.Delivery) (*Message, error) {

	key, ok := delivery.Headers["pf-limit-key"]
	if !ok {
		return nil, fmt.Errorf("missing header pf-limit-key")
	}

	time, ok := delivery.Headers["pf-limit-time"]
	if !ok {
		return nil, fmt.Errorf("missing header pf-limit-time")
	}
	t, _ := strconv.Atoi(time.(string))

	timeValue, ok := delivery.Headers["pf-limit-value"]
	if !ok {
		return nil, fmt.Errorf("missing header pf-limit-value")
	}
	tv, _ := strconv.Atoi(timeValue.(string))

	exchange, ok := delivery.Headers["pf-return-exchange"]
	if !ok || exchange == "" {
		return nil, fmt.Errorf("missing or empty header pf-return-exchange")
	}

	routingKey, ok := delivery.Headers["pf-return-routing-key"]
	if !ok || routingKey == "" {
		return nil, fmt.Errorf("missing or empty header pf-return-routing-key")
	}

	innerMsg := amqp.Publishing{
		Headers:     delivery.Headers,
		Body:        delivery.Body,
		ContentType: delivery.ContentType,
		Priority:    delivery.Priority,
		ReplyTo:     delivery.ReplyTo,
	}

	return &Message{"", key.(string), t, tv, exchange.(string), routingKey.(string), innerMsg}, nil
}
