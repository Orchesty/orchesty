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

	for i := 0; i < 1; i++ {
		p.Publish(amqp.Publishing{Headers: amqp.Table{"pf-limit-key": "#123", "pf-limit-time": "10", "pf-limit-value": "10"}, Body: []byte("My test message")})
		println(i)
	}
}
