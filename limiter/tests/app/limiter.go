package app

import (
	"context"
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"limiter/pkg/app"
	"limiter/pkg/bridge"
	"limiter/pkg/limiter"
	"limiter/pkg/model"
	"limiter/pkg/mongo"
	bridge_test "limiter/tests/bridge"
	"sync"
)

type TestConsumer = *rabbitmq.JsonConsumerMock[model.MessageDto]

func prepareMockApplication() (outputMessages chan bridge.RequestMessage, limiterConsumer TestConsumer, repeaterConsumer TestConsumer, stop func()) {
	messageProcessor, sender, limiterMock, repeatMock := prepareMockApplicationNotStarted()

	ctx, cancel := context.WithCancel(context.Background())
	go messageProcessor.Start(ctx, &sync.WaitGroup{})

	stopFunc := func() {
		limiterMock.Close()
		repeatMock.Close()
		cancel()
	}

	return sender, limiterMock, repeatMock, stopFunc
}

func prepareMockApplicationNotStarted() (processor app.MessageProcessor, outputMessages chan bridge.RequestMessage, limiterConsumer TestConsumer, repeaterConsumer TestConsumer) {
	limiterMock := &rabbitmq.JsonConsumerMock[model.MessageDto]{
		Messages: make(chan rabbitmq.JsonMessageMock[model.MessageDto], 10),
	}
	repeaterMock := &rabbitmq.JsonConsumerMock[model.MessageDto]{
		Messages: make(chan rabbitmq.JsonMessageMock[model.MessageDto], 10),
	}

	mongoSvc := mongo.NewMongoSvc()
	limiterSvc := limiter.NewService()
	cacheSvc := limiter.NewCache()

	_ = mongoSvc.ClearAll()

	go limiterMock.Consume(app.ProcessMessage(mongoSvc, cacheSvc, limiterSvc))
	go repeaterMock.Consume(app.ProcessMessage(mongoSvc, cacheSvc, limiterSvc))

	senderChan := make(chan bridge.RequestMessage, 10)
	bridgeSvc := &bridge_test.BridgeMock{
		ResultMessages: senderChan,
		Mongo:          mongoSvc,
		Limits:         limiterSvc,
		Cache:          cacheSvc,
	}

	messageProcessor := app.NewMessageProcessor(
		bridgeSvc,
		mongoSvc,
		limiterSvc,
		cacheSvc,
	)

	return messageProcessor, senderChan, limiterMock, repeaterMock
}
