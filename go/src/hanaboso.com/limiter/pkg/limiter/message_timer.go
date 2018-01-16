package limiter

import (
	"time"
	"hanaboso.com/limiter/pkg/storage"
	"log"
	"fmt"
	"hanaboso.com/limiter/pkg/rabbitmq"
)

type MessageTimer struct {
	tickers   map[string]*time.Ticker
	storage   storage.Storage
	publisher rabbitmq.Publisher
	timerChan chan *storage.Message
}

func NewMessageTimer(s storage.Storage, p rabbitmq.Publisher, timerChan chan *storage.Message) *MessageTimer {
	return &MessageTimer{storage: s, publisher: p, timerChan: timerChan, tickers: make(map[string]*time.Ticker)}
}

// Init loads and sets timers for already persisted messages and starts new timers handler
func (mt *MessageTimer) Init() {
	mt.loadExistingTimers()
	mt.startHandleNewTimers()
}

func (mt *MessageTimer) addTicker(key string, duration int, count int) {
	mt.tickers[key] = time.NewTicker(time.Second * time.Duration(duration))

	go func() {
		for t := range mt.tickers[key].C {

			hasNext := mt.release(key, count)

			if hasNext == false {
				mt.tickers[key].Stop()
				delete(mt.tickers, key)
				log.Println(fmt.Sprintf("removed ticker for key '%s'", key))
				return
			}

			log.Println(fmt.Sprintf("tick at: %s", t))
		}
	}()
}

func (mt *MessageTimer) release(key string, count int) (bool) {
	msgs, err := mt.storage.Get(key, count)

	if err != nil {
		log.Println(fmt.Sprintf("Release error: %s", err))
		return true
	}

	for _, m := range msgs {
		mt.publisher.Publish(m.Message)
		_, err := mt.storage.Remove(m.LimitKey, m.ID)

		if err != nil {
			log.Println(fmt.Sprintf("Release delete item error: %s", err))
		}
	}

	exists, err := mt.storage.Exists(key)

	if err != nil {
		log.Println(fmt.Sprintf("Release check exist error: %s", err))
		return true
	}

	return exists
}

func (mt *MessageTimer) loadExistingTimers() {
	items, err := mt.storage.GetDistinctFirstItems()
	if err != nil {
		log.Println("Init error:", err.Error())
	}

	for _, i := range items {
		mt.addTicker(i.LimitKey, i.LimitTime, i.LimitValue)
	}
}

func (mt *MessageTimer) startHandleNewTimers() {
	for m := range mt.timerChan {
		if _, ok := mt.tickers[m.LimitKey]; !ok {
			mt.addTicker(m.LimitKey, m.LimitTime, m.LimitValue)
		}
	}
}
