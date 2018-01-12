package main

import (
	"hanaboso.com/utils/rabbitmq"
	"github.com/streadway/amqp"
)

// main runs the
func main() {

	// Queue
	q := rabbitmq.Queue{Name: "my-queue"}
	q.AddBinding(rabbitmq.Binding{Exchange: "my-exchange", RoutingKey: "routing-key"})
	// Exchange
	e := rabbitmq.Exchange{Name: "my-exchange", Type: "direct"}

	r := rabbitmq.NewRabbitMq("127.0.0.10", 5672, "guest", "guest")
	r.AddQueue(q)
	r.AddExchange(e)

	// Setup
	r.Connect()
	r.Setup()

	m := amqp.Publishing{Body: []byte("Test message")}

	p := rabbitmq.NewPublisher(r, "my-queue")
	p.Publish(m)

	r.Disconnect()
}
