package main

import (
	"hanaboso.com/utils/rabbitmq"
	"github.com/streadway/amqp"
	"log"
	"fmt"
)

// main runs the
func main() {

	r := rabbitmq.RabbitMq{Host: "127.0.0.10", Port: 5672, User: "guest", Password: "guest"}
	r.AddQueue(rabbitmq.Queue{Name: "my-queue"})

	// Setup
	r.Connect()
	r.Setup()

	c := rabbitmq.Consumer{RabbitMq: r, Queue: "my-queue"}
	c.Consume(func(msgs <-chan amqp.Delivery) {
		for m := range msgs {
			log.Println(fmt.Sprintf("Recieve message: %s", m))

			m.Ack(false)
		}
	})

	r.Disconnect()
}
