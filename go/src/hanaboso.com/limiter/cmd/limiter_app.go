package main

import (
	"hanaboso.com/limiter/pkg/limiter"
	"os"
	"os/signal"
	"syscall"
	"log"
	"hanaboso.com/limiter/pkg/rabbitmq"
	"hanaboso.com/limiter/pkg/storage"
	"hanaboso.com/utils/env"
	"strconv"
)

// main runs the limiter program
func main() {
	// connects to mongodb
	db := storage.NewMongo(
		env.GetEnv("MONGO_HOST", "127.0.0.10"),
		env.GetEnv("MONGO_DB", "limiter"),
		env.GetEnv("MONGO_COLLECTION", "messages"),
	)
	db.Connect()

	rabbitInput := env.GetEnv("RABBITMQ_INPUT_QUEUE", "limiter_input")
	rabbitPort, _ := strconv.Atoi(env.GetEnv("RABBITMQ_PORT", "5672"))
	conn := rabbitmq.NewConnection(
		env.GetEnv("RABBITMQ_HOST", "127.0.0.10"),
		rabbitPort,
		env.GetEnv("RABBITMQ_USER", "guest"),
		env.GetEnv("RABBITMQ_PASS", "guest"),
	)

	// Input queue
	conn.AddQueue(rabbitmq.Queue{Name: rabbitInput})
	conn.Connect()
	conn.Setup()

	consumer := rabbitmq.NewConsumer(conn, rabbitInput)
	publisher := rabbitmq.NewPublisher(conn, "")
	timerChan := make(chan *storage.Message)
	mt := limiter.NewMessageTimer(db, publisher, timerChan)

	// create limiter
	lim := limiter.NewLimiter(db, consumer, mt, timerChan)

	// starts the tcp server
	tcpServer := limiter.NewTcpServer(lim)
	limiterPort, _ := strconv.Atoi(env.GetEnv("LIMITER_PORT", "3333"))
	go tcpServer.Start(limiterPort)

	lim.Start()

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
