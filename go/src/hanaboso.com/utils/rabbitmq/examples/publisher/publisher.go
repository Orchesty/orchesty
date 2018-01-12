package main

import (
	"hanaboso.com/utils/rabbitmq"
	"github.com/streadway/amqp"
)

// main runs the
func main() {

	// Queue
	q := rabbitmq.Queue{Name: "my-queue"}
	qb := rabbitmq.Binding{Exchange: "my-exchange", RoutingKey: "routing-key"}
	q.AddBinding(qb)
	// Exchange
	e := rabbitmq.Exchange{Name: "my-exchange", Type: "direct"}

	r := rabbitmq.RabbitMq{Host: "127.0.0.10", Port: 5672, User: "guest", Password: "guest"}
	r.AddQueue(q)
	r.AddExchange(e)

	// Setup
	r.Connect()
	r.Setup()

	m := amqp.Publishing{Body: []byte("Test message")}

	p := rabbitmq.Publisher{RabbitMq: r, RoutingKey: "my-queue"}
	p.Publish(m)

	r.Disconnect()
}
