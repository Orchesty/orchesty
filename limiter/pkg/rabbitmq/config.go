package rabbitmq

import "github.com/streadway/amqp"

// Exchange represents the RabbitMQ exchange and its settings
type Exchange struct {
	Name       string
	Type       string
	Durable    bool
	AutoDelete bool
	Internal   bool
	NoWait     bool
	Bindings   []Binding
	Args       amqp.Table
}

// AddBinding adds new binding to the exchange
func (e *Exchange) AddBinding(binding Binding) {
	e.Bindings = append(e.Bindings, binding)
}

// Queue represents RabbitMQ queue and its settings
type Queue struct {
	Name       string
	Durable    bool
	AutoDelete bool
	Exclusive  bool
	NoWait     bool
	Bindings   []Binding
	Args       amqp.Table
}

// AddBinding adds new binding to the queue
func (q *Queue) AddBinding(b Binding) {
	q.Bindings = append(q.Bindings, b)
}

// Binding represents the relation between exchange and queues
type Binding struct {
	Exchange   string
	RoutingKey string
	NoWait     bool
	Args       amqp.Table
}
