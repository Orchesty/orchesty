package limiter

import (
	"hanaboso.com/limiter/pkg/storage"
	"hanaboso.com/limiter/pkg/rabbitmq"
	"github.com/streadway/amqp"
	"log"
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
}

// NewLimiter creates new limiter instance
func NewLimiter(
	store storage.Storage,
	consumer rabbitmq.Consumer,
	msgTimer *MessageTimer,
	timerChan chan *storage.Message,
) (l *limiter) {
	return &limiter{store, consumer, msgTimer, timerChan}
}

// Start initializes the timers and starts consumption
func (l *limiter) Start() {
	l.msgTimer.Init()
	go l.consumer.Consume(func(msg <-chan amqp.Delivery) {
		for m := range msg {
			log.Println(m)

			msg, err := storage.NewMessage(&m)

			if err != nil {
				log.Println(fmt.Sprintf("Message error: %s", err))
			} else {
				log.Println("Message accepted, key: ", msg.LimitKey)
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
	exists, err := l.store.Check(key, time, value)
	if err != nil {
		return false, err
	}

	return !exists, nil
}

// PostponeMessage
func (l *limiter) saveMessage(msg *storage.Message) (error) {
	_, err := l.store.Save(msg)
	if err != nil {
		return err
	}

	return nil
}
