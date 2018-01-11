package main

import (
	"hanaboso.com/utils/rabbitmq"
)

// main runs the
func main() {

	// Queue
	q := rabbitmq.Queue{Name: "my-queue"}
	qb := rabbitmq.Binding{Exchange:"my-exchange", RoutingKey:"routing-key"}
	q.AddBinding(qb)
	// Exchange
	e := rabbitmq.Exchange{Name: "my-exchange", Type: "direct"}

	r := rabbitmq.RabbitMq{Host: "127.0.0.10", Port: 5672, User: "guest", Password: "guest"}
	r.AddQueue(q)
	r.AddExchange(e)

	// Setup
	r.Connect()
	r.Setup()

	done := make(chan bool, 1)

	<-done

	//fmt.Println("I am limiter. Queue " + q.Name + " Exhange " + e.Name)
}
