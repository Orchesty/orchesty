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

func (mt *MessageTimer) addTicker(key string, duration int, count int) {
	mt.tickers[key] = time.NewTicker(time.Second * time.Duration(duration))

	go func() {
		for t := range mt.tickers[key].C {

			next := mt.release(key, count)

			if next == false {
				mt.tickers[key].Stop()
				delete(mt.tickers, key)
				log.Println(fmt.Sprintf("remove tiker for key '%s'", key))
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
	}

	// clear
	exists, err := mt.storage.Exists(key)

	if err != nil {
		log.Println(fmt.Sprintf("Release check exist error: %s", err))
		return true
	}

	return exists
}

func (mt *MessageTimer) Init() {
	for m := range mt.timerChan {
		mt.addTicker(m.LimitKey, m.LimitTime, m.LimitValue)
	}
}

func NewMessageTimer(s storage.Storage, p rabbitmq.Publisher, timerChan chan *storage.Message) *MessageTimer {
	return &MessageTimer{storage: s, publisher: p, timerChan: timerChan, tickers: make(map[string]*time.Ticker)}
}
