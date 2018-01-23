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
	msgTimer  MessageTimer
	timerChan chan *storage.Message
	guard     Guard
	logger    logger.Logger
}

// NewLimiter creates new limiter instance
func NewLimiter(
	store storage.Storage,
	consumer rabbitmq.Consumer,
	msgTimer MessageTimer,
	timerChan chan *storage.Message,
	guard Guard,
	logger logger.Logger,
) *limiter {
	return &limiter{store, consumer, msgTimer, timerChan, guard,logger}
}

// Start initializes the timers and starts consumption
func (l *limiter) Start() {
	l.msgTimer.Init()
	go l.consumer.Consume(func(msg <-chan amqp.Delivery) {
		for m := range msg {
			l.handleAmqpMessage(m)
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

// handleAmqpMessage is called whenever new amqp message is consumed
// should create storage message object and save it, or discard it if the message key is blacklisted
func (l *limiter) handleAmqpMessage(m amqp.Delivery) {
	defer m.Ack(false)

	context := logger.CtxFromDelivery(m)
	l.logger.Info("Limiter received message from RabbitMQ", context)

	msg, err := storage.NewMessage(&m)

	if err != nil {
		context["error"] = err
		l.logger.Error("Limiter cannot create storage message object.", context)
		return
	}

	if l.guard.IsOnBlacklist(msg.LimitKey) {
		context["error"] = fmt.Errorf(fmt.Sprintf("Limit Key '%s' is in blacklist", msg.LimitKey))
		l.logger.Warning("Limiter is discarding message.", context)
		return
	}

	_, err = l.store.Save(msg)
	if err != nil {
		context["error"] = err
		l.logger.Error("Limiter cannot save mesasge to storage.", context)
		return
	}

	l.logger.Info(fmt.Sprintf("Limiter accepted message, key: '%s'", msg.LimitKey), context)
	l.timerChan <- msg
}
