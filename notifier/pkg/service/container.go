package service

import (
	"context"
	"fmt"
	"net/http"
	"time"

	"github.com/hanaboso/go-mongodb"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/redis/go-redis/v9"

	"notifier/pkg/config"
	"notifier/pkg/model"
	"notifier/pkg/rabbit"
	"notifier/pkg/sender"
	"notifier/pkg/storage"
)

var Container container

var shutdown func()

type container struct {
	StatusService       StatusService
	SubscriptionService SubscriptionService
	SSEBroadcaster      *SSEBroadcaster
}

func Load() error {
	connection := &mongodb.Connection{}
	connection.Connect(config.MongoDB.Dsn)

	mongoStorage := storage.NewStorage(connection, config.Logger)

	rmq := rabbit.Connect()

	store := NewThrottleStore()
	presets := BuildPresets()

	helpers := model.EvaluatorHelpers{
		WindowCount: func(ctx context.Context, key string, windowMs int) (int64, error) {
			return store.Increment(ctx, key, windowMs)
		},
	}

	httpSender := sender.NewHttpSender(&http.Client{
		Timeout: time.Duration(config.Dispatch.Timeout) * time.Second,
	}, config.Logger)

	var redisClient *redis.Client
	if rs, ok := store.(*RedisStore); ok {
		redisClient = rs.Client()
	}

	sseBroadcaster := NewSSEBroadcaster()

	bufferService := NewBufferService(redisClient)
	recipientService := NewRecipientService(mongoStorage, config.Logger)
	dispatcherService := NewDispatcherService(httpSender, config.Dispatch.URL, config.App.InstanceID, config.Logger)
	processorService := NewProcessorService(presets, helpers, store, bufferService, recipientService, dispatcherService, mongoStorage, sseBroadcaster, config.Logger)

	msgs := rmq.Consume()

	ctx, cancel := context.WithCancel(context.Background())

	go startConsumer(ctx, msgs, processorService)

	Container = container{
		StatusService:       NewStatusService(connection, rmq, store),
		SubscriptionService: NewSubscriptionService(mongoStorage, config.Logger),
		SSEBroadcaster:      sseBroadcaster,
	}

	shutdown = func() {
		cancel()
		rmq.Close()
		connection.Disconnect()
	}

	return nil
}

func Shutdown() {
	if shutdown != nil {
		shutdown()
	}
}

func startConsumer(ctx context.Context, msgs <-chan amqp.Delivery, processor ProcessorService) {
	for {
		select {
		case <-ctx.Done():
			return
		case msg, ok := <-msgs:
			if !ok {
				return
			}

			if err := processor.Process(ctx, msg.Body); err != nil {
				config.Logger.Error(fmt.Errorf("rejecting message to DLX: %v", err))
				msg.Nack(false, false)
			} else {
				msg.Ack(false)
			}
		}
	}
}
