package main

import (
	"fmt"
	"go.mongodb.org/mongo-driver/mongo"
	"log"
	"os"
	"os/signal"
	"strconv"
	"syscall"
	"time"

	"limiter/pkg/env"
	"limiter/pkg/limiter"
	"limiter/pkg/logger"
	"limiter/pkg/rabbitmq"
	"limiter/pkg/storage"
	"limiter/pkg/tcp"
)

// main runs the limiter program
func main() {
	if err := os.Setenv("APP_NAME", "limiter"); err != nil {
		fmt.Printf("can't set environment APP_NAME [%v]", err)
		os.Exit(1)
	}

	prepareLogger(env.GetEnv("LOG_LEVEL", "info"))
	store := prepareStorage()
	err := createIndexes(store, storage.GetIndexes())
	if err != nil {
		logger.GetLogger().Error(fmt.Sprintf("failed to create indexes [%v]", err), nil)
		//os.Exit(1)
	}

	guard := prepareGuard(store)
	consumer, publisher, err := prepareRabbit()
	if err != nil {
		logger.GetLogger().Error(fmt.Sprintf("failed to prepare rabbit connection [%v]", err), nil)
		os.Exit(1)
	}

	timerChan := make(chan *storage.Message)
	//TODO: this is only fix prevent call fatal in goroutines
	serverFault := make(chan bool, 1)
	mt := limiter.NewMessageTimer(store, publisher, timerChan, logger.GetLogger())
	lim := limiter.NewLimiter(store, consumer, mt, timerChan, guard, logger.GetLogger())

	// starts the tcp server
	tcpServer := tcp.NewTCPServer(lim, logger.GetLogger())
	limiterAddr := env.GetEnv("LIMITER_ADDR", "127.0.0.1:3333")

	go tcpServer.Start(limiterAddr, serverFault)

	lim.Start()

	gracefulShutdown(tcpServer, consumer, publisher, serverFault)
}

func createIndexes(store storage.Storage, indexes []mongo.IndexModel) error {
	for _, index := range indexes {
		if err := store.CreateIndex(index); err != nil {
			return err
		}
	}

	return nil
}

func prepareStorage() storage.Storage {
	db := storage.NewMongo(
		env.GetEnv("MONGO_COLLECTION", "limiter"),
		logger.GetLogger(),
	)
	db.Connect()
	return storage.NewPredictiveCachedStorage(db, time.Hour*24, logger.GetLogger())
}

func prepareRabbit() (rabbitmq.Consumer, rabbitmq.Publisher, error) {
	inputQueue := env.GetEnv("RABBITMQ_INPUT_QUEUE", "pipes.limiter")
	pc := env.GetEnv("RABBITMQ_INPUT_QUEUE_PREFETCH_COUNT", "100")

	prefetchCount, err := strconv.Atoi(pc)
	if err != nil {
		return nil, nil, fmt.Errorf("can't set PREFETCH_COUNT [%v]", err)
	}

	conn := rabbitmq.NewConnection(
		env.GetEnv("RABBITMQ_DSN", ""),
		logger.GetLogger(),
	)

	// Input queue
	conn.AddQueue(rabbitmq.Queue{Name: inputQueue, Durable: true})
	conn.Connect()
	conn.Setup()

	consumer := rabbitmq.NewConsumer(conn, inputQueue, logger.GetLogger(), prefetchCount)
	publisher := rabbitmq.NewPublisher(conn, "", logger.GetLogger())

	return consumer, publisher, nil
}

func prepareLogger(severityLevel string) {
	level, err := logger.ParseLevel(severityLevel)
	if err != nil {
		fmt.Printf("failed parse severity level %s => %s", severityLevel, err.Error())
		return
	}

	logger.GetLogger().SetLevel(level)
	logger.GetLogger().AddHandler(logger.NewLogStashHandler(logger.NewStdOutSender()))
	logger.GetLogger().AddHandler(
		logger.NewLogStashHandler(
			logger.NewUpdSender(
				env.GetEnv("UDP_LOGGER_URL", "logstash:5120"),
			),
		),
	)
}

func prepareGuard(storage storage.Storage) limiter.Guard {
	tooOld := time.Hour * 24 * 365
	guard := limiter.NewLimitGuard(storage, logger.GetLogger())

	// check immediately
	guard.Check(tooOld)

	// check in future time periods
	tick := time.NewTicker(time.Hour)
	go func() {
		for range tick.C {
			guard.Check(tooOld)
		}
	}()

	return guard
}

// gracefulShutdown handles SIGINT and SIGTERM signal to stop the app gracefully
func gracefulShutdown(srv *tcp.Server, c rabbitmq.Consumer, p rabbitmq.Publisher, fault <-chan bool) {
	sigs := make(chan os.Signal, 1)
	quit := make(chan bool, 1)

	closeSources := func() {
		srv.Stop()
		c.Stop()
		p.Stop()

		quit <- true
	}

	go func() {
		<-fault
		log.Println()
		logger.GetLogger().Info("Fault signal from tcp server received", nil)

		closeSources()
	}()

	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		sig := <-sigs
		log.Println()
		logger.GetLogger().Info("Signal received: "+sig.String(), nil)

		closeSources()
	}()

	<-quit
}
