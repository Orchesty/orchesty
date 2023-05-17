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
	"time"
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

	// TODO temporary refresher to see if keys are causing limiter to stop sending messages - remove whole func later on
	go refresh(mongoSvc, cacheSvc, limiterSvc)

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

func refresh(mongoSvc mongo.MongoSvc, cacheSvc *limiter.Cache, limiterSvc *limiter.LimitSvc) {
	for _ = range time.NewTicker(5 * time.Minute).C {
		log.Info().Msg("Refreshing limiter keys based on db")
		existingKeys, err := mongoSvc.GetAllLimitKeys()
		if err != nil {
			log.Fatal().Err(err).Send()
		}

		cacheSvc.ReFromLimits(existingKeys)
		limiterSvc.ReFillLimits(existingKeys)
	}
}
