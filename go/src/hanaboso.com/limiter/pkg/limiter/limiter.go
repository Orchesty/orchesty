package limiter

import (
	"hanaboso.com/limiter/pkg/storage"
	"time"
	"fmt"
	"log"
)

type Limiter interface {
	IsFreeLimit(key string, time string, value string) (bool, error)
	PostponeMessage(msg storage.Message) (error)
}

type limiter struct {
	Db storage.Storage
}

// isFreeLimit returns boolean whether message can be processed or not considering system limits
func (l *limiter) IsFreeLimit(key string, time string, value string) (bool, error) {
	exists, err := l.Db.Exists(key)
	if err != nil {
		return false, err
	}

	return exists, nil
}

// PostponeMessage
func (l *limiter) PostponeMessage(msg storage.Message) (error) {
	_, err := l.Db.Save(msg)
	if err != nil {
		return err
	}

	// TODO - start goroutine to check and publish messages from storage after msg.limitTime
	timer := time.NewTimer(time.Second * time.Duration(msg.LimitTime))
	go l.unleashMessages(timer, msg.LimitKey, msg.LimitValue)

	return nil
}

func (l *limiter) unleashMessages(tim *time.Timer, key string, length int) {
	<- tim.C
	fmt.Println("Timer call:", key)
	_, err := l.Db.Get(key, length)

	if err != nil {
		log.Println("Error unleashing messages", err.Error())
	}

	// TODO - send messages via publisher
}

func NewLimiter(storage storage.Storage) (l *limiter) {
	return &limiter{Db: storage}
}

