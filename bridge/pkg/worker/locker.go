package worker

import (
	"context"
	"sync"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/rs/zerolog/log"
)

const (
	delaySec        = 5
	poisonTTLMs     = 600_000 // 10 minutes
	cleanupInterval = 60      // seconds
)

type hostState struct {
	lockUntil         int64
	consecutiveErrors int
	poisoned          map[string]int64 // correlationId -> poisoned timestamp (unix ms)
}

type locker struct {
	hosts map[string]*hostState
	mutex sync.RWMutex
}

var l locker

func init() {
	l = locker{
		hosts: make(map[string]*hostState),
	}
}

func lockerKey(host, nodeId string) string {
	return host + "|" + nodeId
}

func getOrCreate(key string) *hostState {
	state, ok := l.hosts[key]
	if !ok {
		state = &hostState{}
		l.hosts[key] = state
	}
	return state
}

// RecordFailure tracks a 5xx / connection error for a host+node pair.
// The counter only increments when the previous lock window has expired,
// so parallel goroutines failing simultaneously count as a single round.
func RecordFailure(host, nodeId, correlationId string) {
	l.mutex.Lock()
	defer l.mutex.Unlock()

	key := lockerKey(host, nodeId)
	state := getOrCreate(key)
	now := time.Now().UnixMilli()

	if now >= state.lockUntil {
		state.consecutiveErrors++
	}
	state.lockUntil = now + int64(delaySec*1_000)

	maxFailures := config.App.WorkerMaxFailures
	if maxFailures > 0 && state.consecutiveErrors >= maxFailures {
		if state.poisoned == nil {
			state.poisoned = make(map[string]int64)
		}
		state.poisoned[correlationId] = now
	}
}

// RecordSuccess clears all failure state for a host+node pair.
func RecordSuccess(host, nodeId string) {
	l.mutex.Lock()
	defer l.mutex.Unlock()

	key := lockerKey(host, nodeId)
	state, ok := l.hosts[key]
	if !ok {
		return
	}
	state.consecutiveErrors = 0
	state.lockUntil = 0
	state.poisoned = nil
}

// IsPoisoned returns true if the given correlationId has been marked as
// poisoned for the host+node pair.
func IsPoisoned(host, nodeId, correlationId string) bool {
	if correlationId == "" {
		return false
	}

	l.mutex.RLock()
	defer l.mutex.RUnlock()

	key := lockerKey(host, nodeId)
	state, ok := l.hosts[key]
	if !ok {
		return false
	}
	if state.poisoned == nil {
		return false
	}
	_, poisoned := state.poisoned[correlationId]
	return poisoned
}

// CanSend returns true if the host+node lock has expired and a new HTTP
// request can be attempted.
func CanSend(host, nodeId string) bool {
	l.mutex.RLock()
	defer l.mutex.RUnlock()

	key := lockerKey(host, nodeId)
	state, ok := l.hosts[key]
	if !ok {
		return true
	}
	return time.Now().UnixMilli() >= state.lockUntil
}

// Lock sets the lockUntil timestamp for a host+node pair without affecting
// the failure counter. Retained for API compatibility.
func Lock(host, nodeId string) {
	l.mutex.Lock()
	defer l.mutex.Unlock()

	key := lockerKey(host, nodeId)
	state := getOrCreate(key)
	state.lockUntil = time.Now().UnixMilli() + int64(delaySec*1_000)
}

// StartCleanup runs a background goroutine that removes poisoned entries
// older than poisonTTLMs. Stops when ctx is cancelled.
func StartCleanup(ctx context.Context) {
	ticker := time.NewTicker(cleanupInterval * time.Second)
	defer ticker.Stop()

	for {
		select {
		case <-ctx.Done():
			return
		case <-ticker.C:
			cleanupExpiredPoisons()
		}
	}
}

func cleanupExpiredPoisons() {
	l.mutex.Lock()
	defer l.mutex.Unlock()

	now := time.Now().UnixMilli()
	for key, state := range l.hosts {
		if state.poisoned == nil {
			continue
		}
		for corrId, ts := range state.poisoned {
			if now-ts > poisonTTLMs {
				delete(state.poisoned, corrId)
			}
		}
		if len(state.poisoned) == 0 {
			state.poisoned = nil
		}
		if state.poisoned == nil && state.consecutiveErrors == 0 && now >= state.lockUntil {
			delete(l.hosts, key)
		}
	}

	log.Trace().Int("hosts_tracked", len(l.hosts)).Msg("poison cleanup cycle")
}

// ResetForTest clears all locker state. Only for use in tests.
func ResetForTest() {
	l.mutex.Lock()
	defer l.mutex.Unlock()
	l.hosts = make(map[string]*hostState)
}
