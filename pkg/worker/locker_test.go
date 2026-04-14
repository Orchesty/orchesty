package worker

import (
	"context"
	"testing"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/stretchr/testify/assert"
)

func setMaxFailures(n int) {
	config.App.WorkerMaxFailures = n
}

// simulateDistinctRounds calls RecordFailure n times, resetting lockUntil
// between calls to simulate separate retry rounds separated by 5s each.
func simulateDistinctRounds(host, nodeId, correlationId string, n int) {
	key := lockerKey(host, nodeId)
	for i := 0; i < n; i++ {
		// Expire the lock so the next call counts as a new round
		l.mutex.Lock()
		if state, ok := l.hosts[key]; ok {
			state.lockUntil = 0
		}
		l.mutex.Unlock()

		RecordFailure(host, nodeId, correlationId)
	}
}

func TestRecordFailure_PoisonsAfterMax(t *testing.T) {
	ResetForTest()
	setMaxFailures(10)

	host, nodeId, corrId := "http://worker:8080", "node-1", "batch-abc"

	simulateDistinctRounds(host, nodeId, corrId, 9)
	assert.False(t, IsPoisoned(host, nodeId, corrId), "should not be poisoned after 9 rounds")

	simulateDistinctRounds(host, nodeId, corrId, 1)
	assert.True(t, IsPoisoned(host, nodeId, corrId), "should be poisoned after 10 rounds")
}

func TestRecordFailure_DifferentCorrelation(t *testing.T) {
	ResetForTest()
	setMaxFailures(10)

	host, nodeId := "http://worker:8080", "node-1"
	corrA := "batch-A"
	corrB := "batch-B"

	simulateDistinctRounds(host, nodeId, corrA, 10)
	assert.True(t, IsPoisoned(host, nodeId, corrA), "corrA should be poisoned")
	assert.False(t, IsPoisoned(host, nodeId, corrB), "corrB should NOT be poisoned")
}

func TestRecordFailure_ParallelDedup(t *testing.T) {
	ResetForTest()
	setMaxFailures(10)

	host, nodeId, corrId := "http://worker:8080", "node-1", "batch-parallel"

	// Simulate 10 parallel goroutines failing at the same instant.
	// Only the first call should increment the counter (setting lockUntil).
	// Subsequent calls within the same lock window should NOT increment.
	for i := 0; i < 10; i++ {
		RecordFailure(host, nodeId, corrId)
	}

	key := lockerKey(host, nodeId)
	l.mutex.RLock()
	state := l.hosts[key]
	errors := state.consecutiveErrors
	l.mutex.RUnlock()

	assert.Equal(t, 1, errors, "parallel failures within the same lock window should count as 1")
	assert.False(t, IsPoisoned(host, nodeId, corrId), "should NOT be poisoned after 1 effective round")
}

func TestRecordSuccess_ClearsPoisoned(t *testing.T) {
	ResetForTest()
	setMaxFailures(10)

	host, nodeId, corrId := "http://worker:8080", "node-1", "batch-xyz"

	simulateDistinctRounds(host, nodeId, corrId, 10)
	assert.True(t, IsPoisoned(host, nodeId, corrId))

	RecordSuccess(host, nodeId)
	assert.False(t, IsPoisoned(host, nodeId, corrId), "should be cleared after success")

	key := lockerKey(host, nodeId)
	l.mutex.RLock()
	state := l.hosts[key]
	l.mutex.RUnlock()

	assert.Equal(t, 0, state.consecutiveErrors, "errors should be reset to 0")
}

func TestPerNodeIsolation(t *testing.T) {
	ResetForTest()
	setMaxFailures(10)

	host := "http://worker:8080"
	nodeA := "node-A"
	nodeB := "node-B"
	corrId := "batch-shared"

	simulateDistinctRounds(host, nodeA, corrId, 10)
	assert.True(t, IsPoisoned(host, nodeA, corrId), "nodeA should be poisoned")
	assert.False(t, IsPoisoned(host, nodeB, corrId), "nodeB should NOT be poisoned")

	// nodeB should still be sendable
	assert.True(t, CanSend(host, nodeB), "nodeB should be able to send")
}

func TestPoisonTTL_Expires(t *testing.T) {
	ResetForTest()
	setMaxFailures(10)

	host, nodeId, corrId := "http://worker:8080", "node-1", "batch-ttl"

	simulateDistinctRounds(host, nodeId, corrId, 10)
	assert.True(t, IsPoisoned(host, nodeId, corrId))

	// Backdate the poisoned timestamp to exceed TTL
	key := lockerKey(host, nodeId)
	l.mutex.Lock()
	state := l.hosts[key]
	state.poisoned[corrId] = time.Now().UnixMilli() - poisonTTLMs - 1
	state.consecutiveErrors = 0
	state.lockUntil = 0
	l.mutex.Unlock()

	cleanupExpiredPoisons()

	assert.False(t, IsPoisoned(host, nodeId, corrId), "should be cleaned up after TTL")
}

func TestDisabled_ZeroMaxFailures(t *testing.T) {
	ResetForTest()
	setMaxFailures(0)

	host, nodeId, corrId := "http://worker:8080", "node-1", "batch-disabled"

	simulateDistinctRounds(host, nodeId, corrId, 100)
	assert.False(t, IsPoisoned(host, nodeId, corrId), "poisoning should be disabled when max=0")
}

func TestStartCleanup_StopsOnCancel(t *testing.T) {
	ctx, cancel := context.WithCancel(context.Background())
	done := make(chan struct{})
	go func() {
		StartCleanup(ctx)
		close(done)
	}()

	cancel()

	select {
	case <-done:
		// goroutine exited as expected
	case <-time.After(3 * time.Second):
		t.Fatal("StartCleanup did not exit after context cancellation")
	}
}
