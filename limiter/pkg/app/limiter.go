package app

import (
	"context"
	"github.com/hanaboso/go-utils/pkg/contextx"
	"github.com/rs/zerolog/log"
	"limiter/pkg/bridge"
	"limiter/pkg/limiter"
	"limiter/pkg/mongo"
	"limiter/pkg/rabbit"
	"os"
	"os/signal"
	"sync"
	"syscall"
)

func Start() {
	limiterSvc := limiter.NewService()
	cacheSvc := limiter.NewCache()
	mongoSvc := mongo.NewMongoSvc()

	rabbitSvc := rabbit.NewRabbitSvc()
	messageProcessor := NewMessageProcessor(
		bridge.NewBridgeSvc(mongoSvc, limiterSvc, cacheSvc),
		mongoSvc,
		limiterSvc,
		cacheSvc,
	)

	if err := mongoSvc.UnmarkAllMessages(); err != nil {
		log.Fatal().Err(err).Send()
	}

	existingKeys, err := mongoSvc.GetAllLimitKeys()
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	cacheSvc.FromLimits(existingKeys)
	limiterSvc.FillLimits(existingKeys)

	go rabbitSvc.LimiterConsumer.Consume(ProcessMessage(mongoSvc, cacheSvc, limiterSvc))
	go rabbitSvc.RepeaterConsumer.Consume(ProcessMessage(mongoSvc, cacheSvc, limiterSvc))
	serverStop := StartServer(mongoSvc)

	wg := &sync.WaitGroup{}
	ctx, cancel := context.WithCancel(context.Background())
	go messageProcessor.Start(ctx, wg)

	// Await close call
	closeApp := make(chan bool)
	go func() {
		signals := make(chan os.Signal, 1)
		signal.Notify(signals, syscall.SIGINT, syscall.SIGTERM)

		_ = <-signals
		serverStop(contextx.WithTimeoutSecondsCtx(10))
		cancel()
		rabbitSvc.LimiterConsumer.Close()
		rabbitSvc.RepeaterConsumer.Close()

		close(closeApp)
	}()

	<-closeApp
	wg.Wait()
}
