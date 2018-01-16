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
	"fmt"
)

// main runs the limiter program
func main() {
	// connects to mongodb
	db := storage.NewMongo("127.0.0.10", "test", "messages")
	db.Connect()

	// create limiter
	lim := limiter.NewLimiter(db)

	// starts the tcp server
	tcpServer := limiter.NewTcpServer(lim)
	go tcpServer.Start(3333)

	conn := rabbitmq.NewConnection("127.0.0.10", 5672, "guest", "guest")
	conn.AddQueue(rabbitmq.Queue{Name: "test-q"})
	conn.AddQueue(rabbitmq.Queue{Name: "output"})

	conn.Connect()
	conn.Setup()

	c := rabbitmq.NewConsumer(conn, "test-q")

	timerChan := make (chan *storage.Message)

	go c.Consume(func(msg <-chan amqp.Delivery) {
		for m := range msg {
			log.Println(m)

			msg, err := storage.NewMessage(&m)

			if err != nil {
				log.Println(fmt.Sprintf("Message error: %s", err))
			}else {
				lim.PostponeMessage(msg)
			}

			timerChan <- msg

			m.Ack(false)
		}
	})

	mt := limiter.NewMessageTimer(db, rabbitmq.NewPublisher(conn, "output"), timerChan)

	go mt.Init()

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
