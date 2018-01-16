package limiter

import (
	"hanaboso.com/limiter/pkg/storage"
	"hanaboso.com/limiter/pkg/rabbitmq"
	"github.com/streadway/amqp"
	"log"
	"fmt"
)

type Limiter interface {
	IsFreeLimit(key string, time string, value string) (bool, error)
	Start()
	Stop()
}

type limiter struct {
	db        storage.Storage
	consumer  rabbitmq.Consumer
	msgTimer  *MessageTimer
	timerChan chan *storage.Message
}

// NewLimiter creates new limiter instance
func NewLimiter(
	storage storage.Storage,
	consumer rabbitmq.Consumer,
	msgTimer *MessageTimer,
	timerChan chan *storage.Message,
) (l *limiter) {
	return &limiter{storage, consumer, msgTimer, timerChan}
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
func (l *limiter) IsFreeLimit(key string, time string, value string) (bool, error) {
	exists, err := l.db.Exists(key)
	if err != nil {
		return false, err
	}

	return exists, nil
}


// PostponeMessage
func (l *limiter) saveMessage(msg *storage.Message) (error) {
	_, err := l.db.Save(msg)
	if err != nil {
		return err
	}

	return nil
}
