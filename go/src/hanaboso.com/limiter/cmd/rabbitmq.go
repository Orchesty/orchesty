package main

import (
	"hanaboso.com/utils/rabbitmq"
)

// main runs the
func main() {

	// Queue
	q := rabbitmq.Queue{Name: "my-queue"}
	qb := rabbitmq.Binding{"my-exchange", "routing-key"}
	q.AddBinding(qb)
	// Exchange
	e := rabbitmq.Exchange{Name: "my-exchange"}

	r := rabbitmq.RabbitMq{}
	r.AddQueue(q)
	r.AddExchange(e)

	// Setup
	r.Setup()

	//fmt.Println("I am limiter. Queue " + q.Name + " Exhange " + e.Name)
}
