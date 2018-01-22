package main

import (
	"hanaboso.com/limiter/pkg/rabbitmq"
	"github.com/streadway/amqp"
	"hanaboso.com/limiter/pkg/logger"
	"hanaboso.com/utils/env"
)

func main() {
	logger.GetLogger().AddHandler(logger.NewLogStashHandler(logger.NewStdOutSender()))
	logger.GetLogger().AddHandler(
		logger.NewLogStashHandler(
			logger.NewUpdSender(
				env.GetEnv("LOGSTASH_HOST", "logstash"),
				env.GetEnv("LOGSTASH_PORT", "5120"),
			),
		),
	)

	conn := rabbitmq.NewConnection("127.0.0.10", 5672, "guest", "guest", logger.GetLogger())
	conn.AddQueue(rabbitmq.Queue{Name: "pipes.limiter"})
	q := rabbitmq.Queue{Name: "pipes.output_queue_limiter"}
	q.AddBinding(rabbitmq.Binding{Exchange: "limiter-exchange", RoutingKey: "pipes.output_queue_limiter"})
	conn.AddQueue(q)
	conn.AddExchange(rabbitmq.Exchange{Name: "limiter-exchange", Type: "direct"})

	conn.Connect()
	conn.Setup()

	p := rabbitmq.NewPublisher(conn, "pipes.limiter", logger.GetLogger())

	for i := 0; i < 1; i++ {
		p.Publish(amqp.Publishing{Headers: amqp.Table{
			"pf-limit-key":                "#123",
			"pf-limit-time":               "1",
			"pf-limit-value":              "10",
			"pf-limit-return-exchange":    "limiter-exchange",
			"pf-limit-return-routing-key": "pipes.output_queue_limiter",
		}, Body: []byte("My test message")})
		println(i)
	}
}
