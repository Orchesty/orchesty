package rabbitmq

import "github.com/streadway/amqp"

// Queue struct of queue
type Queue struct {
	Name       string
	Durable    bool
	AutoDelete bool
	Exclusive  bool
	NoWait     bool
	Args       amqp.Table
}
