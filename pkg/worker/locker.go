package worker

import (
	"sync"
	"time"
)

const delay = 5 * 60 * 1000

var l locker

type locker struct {
	locks map[string]int64
	mutex sync.RWMutex
}

func Lock(host string) {
	l.mutex.Lock()
	l.locks[host] = time.Now().UnixMilli() + delay
	l.mutex.Unlock()
}

func CanSend(host string) bool {
	l.mutex.RLock()
	val, ok := l.locks[host]
	l.mutex.RUnlock()

	return !ok || val < time.Now().UnixMilli()
}

func init() {
	l = locker{
		locks: make(map[string]int64),
		mutex: sync.RWMutex{},
	}
}
