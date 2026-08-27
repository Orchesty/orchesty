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

// ageLastFailure backdates the failure bookkeeping for a host+node pair by ms
// milliseconds and expires the lock window, so the next RecordFailure counts as
// a fresh round.
func ageLastFailure(host, nodeId string, ms int64) {
	l.mutex.Lock()
	defer l.mutex.Unlock()
	if state, ok := l.hosts[lockerKey(host, nodeId)]; ok {
		state.lastFailureAt -= ms
		state.lockUntil = 0
	}
}

func consecutiveErrors(host, nodeId string) int {
	l.mutex.RLock()
	defer l.mutex.RUnlock()
	state, ok := l.hosts[lockerKey(host, nodeId)]
	if !ok {
		return 0
	}
	return state.consecutiveErrors
}

func hostTracked(host, nodeId string) bool {
	l.mutex.RLock()
	defer l.mutex.RUnlock()
	_, ok := l.hosts[lockerKey(host, nodeId)]
	return ok
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

// A node whose only traffic is one nightly batch used to accumulate one failure
// round per night — with no success in between to reset the counter — until a
// single transient blip poisoned the whole run.
func TestRecordFailure_CounterDecaysAfterTTL(t *testing.T) {
	ResetForTest()
	setMaxFailures(10)

	host, nodeId, corrId := "http://worker:8080", "node-1", "batch-nightly"

	simulateDistinctRounds(host, nodeId, corrId, 9)
	assert.False(t, IsPoisoned(host, nodeId, corrId), "should not be poisoned after 9 rounds")

	// Nothing failed for longer than the TTL: the next failure starts a new incident.
	ageLastFailure(host, nodeId, failureTTLMs+1)
	RecordFailure(host, nodeId, corrId)

	assert.False(t, IsPoisoned(host, nodeId, corrId), "a failure after the TTL must not poison")
	assert.Equal(t, 1, consecutiveErrors(host, nodeId), "counter should restart at 1")
}

func TestRecordFailure_CounterPersistsWithinTTL(t *testing.T) {
	ResetForTest()
	setMaxFailures(10)

	host, nodeId, corrId := "http://worker:8080", "node-1", "batch-incident"

	simulateDistinctRounds(host, nodeId, corrId, 9)
	assert.False(t, IsPoisoned(host, nodeId, corrId))

	// Still the same incident — rounds inside the TTL keep accumulating.
	ageLastFailure(host, nodeId, failureTTLMs/2)
	RecordFailure(host, nodeId, corrId)

	assert.True(t, IsPoisoned(host, nodeId, corrId), "rounds within the TTL must still poison at the threshold")
	assert.Equal(t, 10, consecutiveErrors(host, nodeId))
}

func TestCleanup_ClearsStaleCounter(t *testing.T) {
	ResetForTest()
	setMaxFailures(10)

	host, nodeId, corrId := "http://worker:8080", "node-1", "batch-stale"

	simulateDistinctRounds(host, nodeId, corrId, 5)
	assert.Equal(t, 5, consecutiveErrors(host, nodeId))

	ageLastFailure(host, nodeId, failureTTLMs+1)
	cleanupExpiredPoisons()

	assert.Equal(t, 0, consecutiveErrors(host, nodeId), "stale counter should be reset")
	assert.False(t, hostTracked(host, nodeId), "idle host entry should be dropped")
}

func TestCleanup_KeepsRecentCounter(t *testing.T) {
	ResetForTest()
	setMaxFailures(10)

	host, nodeId, corrId := "http://worker:8080", "node-1", "batch-recent"

	simulateDistinctRounds(host, nodeId, corrId, 5)
	cleanupExpiredPoisons()

	assert.Equal(t, 5, consecutiveErrors(host, nodeId), "a counter within the TTL must survive cleanup")
	assert.True(t, hostTracked(host, nodeId), "host entry with an active counter must be kept")
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
