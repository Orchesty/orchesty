package main

import (
	"hanaboso.com/limiter/pkg/limiter"
	"os"
	"os/signal"
	"syscall"
	"log"
	"hanaboso.com/limiter/pkg/rabbitmq"
	"github.com/streadway/amqp"
	"hanaboso.com/limiter/pkg/storage"
)

// main runs the limiter program
func main() {
	lim := limiter.Limiter{}
	tcpServer := limiter.NewTcpServer(&lim)
	go tcpServer.Start(3333)

	r := rabbitmq.NewRabbitMq("rabbitmq", 5672, "guest", "guest")
	r.AddQueue(rabbitmq.Queue{Name: "test-q"})

	r.Connect()
	r.Setup()

	c := rabbitmq.NewConsumer(r, "test-q")

	s:= storage.NewStorage("mongodb", "test", "messages")
	s.Connect()

	mes := storage.Message{LimitKey: "123"}

	s.Save(mes)

	go c.Consume(func(msg <-chan amqp.Delivery) {
		for m := range msg {
			log.Println(m)

			m.Ack(false)
		}
	})

	gracefulShutdown(tcpServer)
}

// gracefulShutdown handles SIGINT and SIGTERM signal to stop the app gracefully
func gracefulShutdown(srv *limiter.TcpServer) {
	sigs := make(chan os.Signal, 1)
	quit := make(chan bool, 1)

	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		sig := <-sigs
		log.Println()
		log.Println("Signal received: ", sig)

		srv.Stop()

		quit <- true
	}()

	<-quit
}
