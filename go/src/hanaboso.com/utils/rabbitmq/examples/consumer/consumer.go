package main

import (
	"hanaboso.com/utils/rabbitmq"
	"github.com/streadway/amqp"
	"log"
	"fmt"
)

// main runs the
func main() {

	r := rabbitmq.NewRabbitMq("127.0.0.10", 5672, "guest", "guest")
	r.AddQueue(rabbitmq.Queue{Name: "my-queue"})

	// Setup
	r.Connect()
	r.Setup()

	c := rabbitmq.NewConsumer(r, "my-queue")
	c.Consume(func(msgs <-chan amqp.Delivery) {
		for m := range msgs {
			log.Println(fmt.Sprintf("Recieve message: %s", m))

			m.Ack(false)
		}
	})

	r.Disconnect()
}
