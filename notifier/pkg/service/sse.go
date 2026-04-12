package service

import (
	"sync"

	"notifier/pkg/model"
)

type SSEBroadcaster struct {
	mu          sync.RWMutex
	subscribers map[chan model.InAppNotification]struct{}
}

func NewSSEBroadcaster() *SSEBroadcaster {
	return &SSEBroadcaster{
		subscribers: make(map[chan model.InAppNotification]struct{}),
	}
}

func (b *SSEBroadcaster) Subscribe() chan model.InAppNotification {
	ch := make(chan model.InAppNotification, 64)
	b.mu.Lock()
	b.subscribers[ch] = struct{}{}
	b.mu.Unlock()
	return ch
}

func (b *SSEBroadcaster) Unsubscribe(ch chan model.InAppNotification) {
	b.mu.Lock()
	delete(b.subscribers, ch)
	b.mu.Unlock()
	close(ch)
}

func (b *SSEBroadcaster) Broadcast(n model.InAppNotification) {
	b.mu.RLock()
	defer b.mu.RUnlock()

	for ch := range b.subscribers {
		select {
		case ch <- n:
		default:
			// slow subscriber, drop message
		}
	}
}
