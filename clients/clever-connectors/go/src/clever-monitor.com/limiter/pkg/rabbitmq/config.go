package rabbitmq

import "github.com/streadway/amqp"

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

func (q *Exchange) AddBinding(binding Binding) {
	q.Bindings = append(q.Bindings, binding)
}

type Queue struct {
	Name       string
	Durable    bool
	AutoDelete bool
	Exclusive  bool
	NoWait     bool
	Bindings   []Binding
	Args       amqp.Table
}

func (q *Queue) AddBinding(b Binding) {
	q.Bindings = append(q.Bindings, b)
}

type Binding struct {
	Exchange   string
	RoutingKey string
	NoWait     bool
	Args       amqp.Table
}
