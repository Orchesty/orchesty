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
	// connects to mongodb
	db := storage.NewMongo("127.0.0.1", "test", "messages")
	db.Connect()

	// create limiter
	lim := limiter.NewLimiter(db)

	// starts the tcp server
	tcpServer := limiter.NewTcpServer(lim)
	go tcpServer.Start(3333)

	conn := rabbitmq.NewConnection("127.0.0.10", 5672, "guest", "guest")
	conn.AddQueue(rabbitmq.Queue{Name: "test-q"})

	conn.Connect()
	conn.Setup()

	c := rabbitmq.NewConsumer(conn, "test-q")

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
