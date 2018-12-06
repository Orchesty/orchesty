package rabbitmq

import (
	"github.com/streadway/amqp"
	"starting-point/pkg/config"
)

// Queue struct of queue
type Queue struct {
	Name       string
	Durable    bool
	AutoDelete bool
	Exclusive  bool
	NoWait     bool
	Args       amqp.Table
}

// GetProcessCounterQueue returns Queue conf
func GetProcessCounterQueue() *Queue {
	return &Queue{Name: config.Config.RabbitMQ.CounterQueueName, Durable: config.Config.RabbitMQ.CounterQueueDurable, NoWait: false}
}
