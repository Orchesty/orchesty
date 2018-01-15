package storage

import (
	"github.com/streadway/amqp"
)

type Message struct {
	LimitKey   string
	LimitTime  int
	LimitValue int
	Message    amqp.Publishing
}

// NewMessage creates storage Message struct by converting amqp Delivery to Publishing message and adding limit info
func NewMessage(key string, time int, value int, delivery amqp.Delivery) *Message {
	innerMsg := amqp.Publishing{
		Headers: delivery.Headers,
		ContentType: delivery.ContentType,
		Priority: delivery.Priority,
		Body: delivery.Body,
	}
	return &Message{key, time, value, innerMsg}
}
