package storage

import (
	"github.com/streadway/amqp"
	"fmt"
	"strconv"
)

type Message struct {
	LimitKey   string
	LimitTime  int
	LimitValue int
	Message    amqp.Publishing
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

	innerMsg := amqp.Publishing{
		Headers:     delivery.Headers,
		ContentType: delivery.ContentType,
		Priority:    delivery.Priority,
		Body:        delivery.Body,
	}

	return &Message{key.(string), t, tv, innerMsg}, nil
}
