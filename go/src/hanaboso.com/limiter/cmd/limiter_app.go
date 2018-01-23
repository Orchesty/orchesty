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
	"hanaboso.com/limiter/pkg/logger"
	"time"
	"hanaboso.com/limiter/pkg/tcp"
)

// main runs the limiter program
func main() {
	prepareLogger()
	store := prepareStorage()
	consumer, publisher := prepareRabbit()

	timerChan := make(chan *storage.Message)
	mt := limiter.NewMessageTimer(store, publisher, timerChan, logger.GetLogger())
	lim := limiter.NewLimiter(store, consumer, mt, timerChan, logger.GetLogger())

	// starts the tcp server
	tcpServer := tcp.NewTcpServer(lim, logger.GetLogger())
	limiterPort, _ := strconv.Atoi(env.GetEnv("LIMITER_PORT", "3333"))
	go tcpServer.Start(limiterPort)

	lim.Start()

	gracefulShutdown(tcpServer, consumer, publisher)
}

func prepareStorage() storage.Storage {
	db := storage.NewMongo(
		env.GetEnv("MONGO_HOST", "mongodb"),
		env.GetEnv("MONGO_DB", "limiter"),
		env.GetEnv("MONGO_COLLECTION", "messages"),
		logger.GetLogger(),
	)
	db.Connect()
	return storage.NewPredictiveCachedStorage(db, time.Hour*24, logger.GetLogger())
}

func prepareRabbit() (rabbitmq.Consumer, rabbitmq.Publisher) {
	inputQueue := env.GetEnv("RABBITMQ_INPUT_QUEUE", "pipes.limiter")
	rabbitPort, _ := strconv.Atoi(env.GetEnv("RABBITMQ_PORT", "5672"))
	conn := rabbitmq.NewConnection(
		env.GetEnv("RABBITMQ_HOST", "rabbitmq"),
		rabbitPort,
		env.GetEnv("RABBITMQ_USER", "guest"),
		env.GetEnv("RABBITMQ_PASS", "guest"),
		logger.GetLogger(),
	)

	// Input queue
	conn.AddQueue(rabbitmq.Queue{Name: inputQueue})
	conn.Connect()
	conn.Setup()

	consumer := rabbitmq.NewConsumer(conn, inputQueue, logger.GetLogger())
	publisher := rabbitmq.NewPublisher(conn, "", logger.GetLogger())

	return consumer, publisher
}

func prepareLogger() {
	logger.GetLogger().AddHandler(logger.NewLogStashHandler(logger.NewStdOutSender()))
	logger.GetLogger().AddHandler(
		logger.NewLogStashHandler(
			logger.NewUpdSender(
				env.GetEnv("LOGSTASH_HOST", "logstash"),
				env.GetEnv("LOGSTASH_PORT", "5120"),
			),
		),
	)
}

// gracefulShutdown handles SIGINT and SIGTERM signal to stop the app gracefully
func gracefulShutdown(srv *tcp.Server, c rabbitmq.Consumer, p rabbitmq.Publisher) {
	sigs := make(chan os.Signal, 1)
	quit := make(chan bool, 1)

	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		sig := <-sigs
		log.Println()
		logger.GetLogger().Info("Signal received: " + sig.String(), nil)

		srv.Stop()
		c.Stop()
		p.Stop()

		quit <- true
	}()

	<-quit
}
