package service

import (
	"sync"
)

// Reply event kinds delivered to the reply-to callback by the bridge
const (
	SyncEvent_Result   = "result"
	SyncEvent_Error    = "error"
	SyncEvent_Pending  = "pending"
	SyncEvent_Stopped  = "stopped"
	SyncEvent_Confirm  = "confirm"
	SyncEvent_Question = "question"
)

// SyncEvent is one callback received for a waiting run-sync request
type SyncEvent struct {
	Kind string
	Body []byte
}

// SyncWaiter keeps in-memory waiters of run-sync requests handled by this instance, keyed by reply-id.
// The reply-id is a random UUID and is the only thing that ties a callback to a waiting request.
type SyncWaiter struct {
	mu      sync.Mutex
	waiters map[string]chan SyncEvent
}

// Waiter is the process-wide waiter registry
var Waiter = NewSyncWaiter()

func NewSyncWaiter() *SyncWaiter {
	return &SyncWaiter{waiters: map[string]chan SyncEvent{}}
}

// Register starts waiting for callbacks of the reply id
func (w *SyncWaiter) Register(replyId string) <-chan SyncEvent {
	ch := make(chan SyncEvent, 8)

	w.mu.Lock()
	w.waiters[replyId] = ch
	w.mu.Unlock()

	return ch
}

// Resolve hands a callback over to its waiter. It returns false when nobody waits
// (unknown id, request already answered or timed out) or the waiter's buffer is full.
func (w *SyncWaiter) Resolve(replyId string, event SyncEvent) bool {
	w.mu.Lock()
	ch, ok := w.waiters[replyId]
	w.mu.Unlock()
	if !ok {
		return false
	}

	select {
	case ch <- event:
		return true
	default:
		return false
	}
}

// Cancel stops waiting; later callbacks of the reply id are ignored
func (w *SyncWaiter) Cancel(replyId string) {
	w.mu.Lock()
	delete(w.waiters, replyId)
	w.mu.Unlock()
}
