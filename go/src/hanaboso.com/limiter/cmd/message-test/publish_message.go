package main

import (
	"hanaboso.com/limiter/pkg/rabbitmq"
	"github.com/streadway/amqp"
)

func main() {
	conn := rabbitmq.NewConnection("127.0.0.10", 5672, "guest", "guest")
	conn.AddQueue(rabbitmq.Queue{Name: "test-q"})

	conn.Connect()
	conn.Setup()

	p := rabbitmq.NewPublisher(conn, "test-q")
	p.Publish(amqp.Publishing{Headers: amqp.Table{"limit-key": "#123"}, Body: []byte("My test message")})
}
