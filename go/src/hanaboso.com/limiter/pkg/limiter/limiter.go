package limiter

import (
	"hanaboso.com/limiter/pkg/storage"
	"hanaboso.com/limiter/pkg/rabbitmq"
	"github.com/streadway/amqp"
	"hanaboso.com/limiter/pkg/logger"
	"fmt"
)

type Limiter interface {
	IsFreeLimit(key string, time int, value int) (bool, error)
	Start()
	Stop()
}

type limiter struct {
	store     storage.CheckerSaver
	consumer  rabbitmq.Consumer
	msgTimer  *MessageTimer
	timerChan chan *storage.Message
	logger    logger.Logger
}

// NewLimiter creates new limiter instance
func NewLimiter(
	store storage.Storage,
	consumer rabbitmq.Consumer,
	msgTimer *MessageTimer,
	timerChan chan *storage.Message,
	logger logger.Logger,
) (l *limiter) {
	return &limiter{store, consumer, msgTimer, timerChan, logger}
}

// Start initializes the timers and starts consumption
func (l *limiter) Start() {
	l.msgTimer.Init()
	go l.consumer.Consume(func(msg <-chan amqp.Delivery) {
		for m := range msg {
			context := logger.CtxFromDelivery(m)
			l.logger.Info("Received message from RabbitMQ", context)

			msg, err := storage.NewMessage(&m)

			if err != nil {
				context["error"] = err
				l.logger.Error("Limiter create message error", context)
			} else {
				l.logger.Info(fmt.Sprintf("Message accepted, key: %s", msg.LimitKey), context)
				l.saveMessage(msg)
			}

			l.timerChan <- msg

			m.Ack(false)
		}
	})
}

// Stop stops the limiter safely
func (l *limiter) Stop() {
	l.consumer.Stop()
}

// isFreeLimit returns boolean whether message can be processed or not considering system limits
func (l *limiter) IsFreeLimit(key string, time int, value int) (bool, error) {
	can, err := l.store.CanHandle(key, time, value)
	if err != nil {
		return false, err
	}

	return can, nil
}

// PostponeMessage
func (l *limiter) saveMessage(msg *storage.Message) (error) {
	_, err := l.store.Save(msg)
	if err != nil {
		return err
	}

	return nil
}
