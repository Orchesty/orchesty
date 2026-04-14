package bridge

import (
	"testing"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/config"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/stretchr/testify/assert"
)

type testPublisher struct{}

func (t testPublisher) Publish(amqp.Publishing) error {
	return nil
}

func resetTrashDedup() {
	globalTrashDedup = &trashDedupTracker{
		entries: make(map[string]*trashDedupEntry),
		events:  events{publisher: testPublisher{}},
	}
}

func TestShouldTrash_UnderLimit(t *testing.T) {
	resetTrashDedup()
	config.App.TrashDuplicationLimit = 3

	assert.True(t, ShouldTrash("node-1", "corr-1", "error msg"))

	RecordTrashed("node-1", "corr-1", "error msg")
	assert.True(t, ShouldTrash("node-1", "corr-1", "error msg"))

	RecordTrashed("node-1", "corr-1", "error msg")
	assert.True(t, ShouldTrash("node-1", "corr-1", "error msg"))
}

func TestShouldTrash_AtLimit(t *testing.T) {
	resetTrashDedup()
	config.App.TrashDuplicationLimit = 3

	RecordTrashed("node-1", "corr-1", "error msg")
	RecordTrashed("node-1", "corr-1", "error msg")
	RecordTrashed("node-1", "corr-1", "error msg")

	assert.False(t, ShouldTrash("node-1", "corr-1", "error msg"), "should reject at limit")
}

func TestShouldTrash_DifferentGroups(t *testing.T) {
	resetTrashDedup()
	config.App.TrashDuplicationLimit = 2

	RecordTrashed("node-1", "corr-1", "error A")
	RecordTrashed("node-1", "corr-1", "error A")

	assert.False(t, ShouldTrash("node-1", "corr-1", "error A"), "group A should be at limit")
	assert.True(t, ShouldTrash("node-1", "corr-1", "error B"), "group B should still be OK")
	assert.True(t, ShouldTrash("node-2", "corr-1", "error A"), "different node should be OK")
}

func TestShouldTrash_Disabled(t *testing.T) {
	resetTrashDedup()
	config.App.TrashDuplicationLimit = 0

	for i := 0; i < 100; i++ {
		assert.True(t, ShouldTrash("node-1", "corr-1", "error msg"), "should always allow when disabled")
	}
}

func TestShouldTrash_NilTracker(t *testing.T) {
	globalTrashDedup = nil
	config.App.TrashDuplicationLimit = 1

	assert.True(t, ShouldTrash("node-1", "corr-1", "error msg"), "should allow when tracker is nil")
}

func TestTrashDedupKey(t *testing.T) {
	key := trashDedupKey("node-1", "corr-1", "error msg")
	assert.Equal(t, "node-1|corr-1|error msg", key)
}

func TestCleanupTrashDedup_RemovesExpired(t *testing.T) {
	resetTrashDedup()
	config.App.TrashDuplicationLimit = 5

	RecordTrashed("node-1", "corr-1", "error msg")

	globalTrashDedup.mu.Lock()
	key := trashDedupKey("node-1", "corr-1", "error msg")
	globalTrashDedup.entries[key].lastStore = time.Now().Add(-trashDedupTTL - time.Minute)
	globalTrashDedup.mu.Unlock()

	cleanupTrashDedup()

	globalTrashDedup.mu.Lock()
	_, exists := globalTrashDedup.entries[key]
	globalTrashDedup.mu.Unlock()

	assert.False(t, exists, "expired entry should be cleaned up")
}

func TestCleanupTrashDedup_KeepsFresh(t *testing.T) {
	resetTrashDedup()
	config.App.TrashDuplicationLimit = 5

	RecordTrashed("node-1", "corr-1", "error msg")

	cleanupTrashDedup()

	globalTrashDedup.mu.Lock()
	key := trashDedupKey("node-1", "corr-1", "error msg")
	_, exists := globalTrashDedup.entries[key]
	globalTrashDedup.mu.Unlock()

	assert.True(t, exists, "fresh entry should not be cleaned up")
}

func TestRecordTrashed_NotificationOnce(t *testing.T) {
	resetTrashDedup()
	config.App.TrashDuplicationLimit = 2

	RecordTrashed("node-1", "corr-1", "error msg")

	globalTrashDedup.mu.Lock()
	key := trashDedupKey("node-1", "corr-1", "error msg")
	entry := globalTrashDedup.entries[key]
	notified1 := entry.notified
	globalTrashDedup.mu.Unlock()
	assert.False(t, notified1, "should not be notified before limit")

	RecordTrashed("node-1", "corr-1", "error msg")

	globalTrashDedup.mu.Lock()
	entry = globalTrashDedup.entries[key]
	notified2 := entry.notified
	globalTrashDedup.mu.Unlock()
	assert.True(t, notified2, "should be notified at limit")
}
