package limiter

import (
	"hanaboso.com/limiter/pkg/storage"
	"time"
	_ "fmt"
	_ "log"
)

type Limiter interface {
	IsFreeLimit(key string, time string, value string) (bool, error)
	PostponeMessage(msg *storage.Message) (error)
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
func (l *limiter) PostponeMessage(msg *storage.Message) (error) {
	_, err := l.Db.Save(msg)
	if err != nil {
		return err
	}

	return nil
}

func NewLimiter(storage storage.Storage) (l *limiter) {
	return &limiter{Db: storage}
}

