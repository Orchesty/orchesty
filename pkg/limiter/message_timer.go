package limiter

import (
	"limiter/pkg/logger"
	"limiter/pkg/rabbitmq"
	"limiter/pkg/storage"

	"fmt"
	"log"
	"time"
)

// MessageTimer represents the timer that checks stored messages if they can become free
type MessageTimer interface {
	Init()
}

type messageTimer struct {
	tickers   map[string]*time.Ticker
	storage   storage.Storage
	publisher rabbitmq.Publisher
	timerChan chan *storage.Message
	logger    logger.Logger
}

// NewMessageTimer return a new MessageTimer instance
func NewMessageTimer(
	s storage.Storage,
	p rabbitmq.Publisher,
	timerChan chan *storage.Message,
	logger logger.Logger,
) MessageTimer {
	return &messageTimer{
		storage:   s,
		publisher: p,
		timerChan: timerChan,
		tickers:   make(map[string]*time.Ticker),
		logger:    logger,
	}
}

// Init loads and sets timers for already persisted messages and starts new timers handler
func (mt *messageTimer) Init() {
	mt.loadExistingTimers()
	go mt.startHandleNewTimers()
}

func (mt *messageTimer) addTicker(key string, duration int, count int) {
	mt.tickers[key] = time.NewTicker(time.Second * time.Duration(duration))
	mt.logger.Info(fmt.Sprintf("Added ticker for key '%s'", key), nil)
	go func() {
		for t := range mt.tickers[key].C {
			mt.logger.Info(fmt.Sprintf("Tick for key: '%s' at: %s", key, t), nil)

			hasNext := mt.release(key, count)
			if hasNext == false {
				mt.tickers[key].Stop()
				delete(mt.tickers, key)
				mt.logger.Info(fmt.Sprintf("Removed ticker for key '%s'", key), nil)
				return
			}

		}
	}()
}

func (mt *messageTimer) release(key string, count int) bool {
	msgs, err := mt.storage.Get(key, count)

	if err != nil {
		mt.logger.Error(fmt.Sprintf("Release could not get messages from storage. Error: %s", err), logger.Context{"error": err})
		return true
	}

	for _, m := range msgs {
		mt.publisher.SetRoutingKey(m.ReturnRoutingKey)
		mt.publisher.SetExchange(m.ReturnExchange)
		mt.publisher.Publish(m.Message)
		mt.deleteMessage(m)
	}

	exists := false
	if msgs == nil {
		mt.storage.ClearCacheItem(key, 0)
	} else {
		//todo: proc kdyz je v mongu nic stale se kouka na kes
		exists, err = mt.storage.Exists(key)

		if err != nil {
			mt.logger.Error(fmt.Sprintf("Release could not check if some messages exist for key %s Error: %s", key, err), logger.Context{"error": err})
			return true
		}
	}

	return exists
}

func (mt *messageTimer) loadExistingTimers() {
	items, err := mt.storage.GetDistinctFirstItems()
	if err != nil {
		log.Println("Init error:", err.Error())
	}

	for _, i := range items {
		mt.addTicker(i.LimitKey, i.LimitTime, i.LimitValue)
	}
}

func (mt *messageTimer) startHandleNewTimers() {
	for m := range mt.timerChan {
		if _, ok := mt.tickers[m.LimitKey]; !ok {
			mt.addTicker(m.LimitKey, m.LimitTime, m.LimitValue)
		}
	}
}

// deleteMessage removes message from storage or logs an error
func (mt *messageTimer) deleteMessage(m *storage.Message) {
	_, err := mt.storage.Remove(m.LimitKey, m.ID)

	if err != nil {
		mt.logger.Error(fmt.Sprintf("Message timer cannot delete message from storage. Error: %s", err), logger.Context{"error": err})
	}
}
