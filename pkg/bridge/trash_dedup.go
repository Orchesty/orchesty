package bridge

import (
	"context"
	"fmt"
	"sync"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/rs/zerolog/log"
)

const (
	trashDedupTTL             = 10 * time.Minute
	trashDedupCleanupInterval = 60 * time.Second
)

type trashDedupEntry struct {
	count     int
	lastStore time.Time
	notified  bool
}

type trashDedupTracker struct {
	entries map[string]*trashDedupEntry
	mu      sync.Mutex
	events  events
}

var globalTrashDedup *trashDedupTracker

func initTrashDedup(ev events) {
	globalTrashDedup = &trashDedupTracker{
		entries: make(map[string]*trashDedupEntry),
		events:  ev,
	}
}

func trashDedupKey(nodeId, correlationId, resultMessage string) string {
	return nodeId + "|" + correlationId + "|" + resultMessage
}

// ShouldTrash returns true if the message should be stored in trash (under limit).
// Returns false if the dedup limit is exceeded for this group.
func ShouldTrash(nodeId, correlationId, resultMessage string) bool {
	limit := config.App.TrashDuplicationLimit
	if limit <= 0 {
		return true
	}
	if globalTrashDedup == nil {
		return true
	}

	key := trashDedupKey(nodeId, correlationId, resultMessage)
	globalTrashDedup.mu.Lock()
	defer globalTrashDedup.mu.Unlock()

	entry, exists := globalTrashDedup.entries[key]
	if !exists {
		return true
	}

	return entry.count < limit
}

// RecordTrashed increments the counter for a trash group after storing.
// Sends a one-time notification when the limit is first hit.
func RecordTrashed(nodeId, correlationId, resultMessage string) {
	limit := config.App.TrashDuplicationLimit
	if limit <= 0 || globalTrashDedup == nil {
		return
	}

	key := trashDedupKey(nodeId, correlationId, resultMessage)
	globalTrashDedup.mu.Lock()
	defer globalTrashDedup.mu.Unlock()

	entry, exists := globalTrashDedup.entries[key]
	if !exists {
		entry = &trashDedupEntry{}
		globalTrashDedup.entries[key] = entry
	}

	entry.count++
	entry.lastStore = time.Now()

	if entry.count >= limit && !entry.notified {
		entry.notified = true
		count := entry.count

		msg := fmt.Sprintf("trash dedup limit reached for node=%s correlation=%s: %d/%d", nodeId, correlationId, count, limit)
		log.Warn().
			Str("node_id", nodeId).
			Str("correlation_id", correlationId).
			Int("count", count).
			Int("limit", limit).
			Msg("trash dedup limit reached, further identical messages will be discarded")

		ev := globalTrashDedup.events
		go func() {
			ev.sendLimitOverflowEvent("trash_duplication", float64(count), float64(limit), 0, msg)
			sendLimitOverflowStatus("trash_duplication", float64(count), float64(limit), 0, msg)
		}()
	}
}

func StartTrashDedupCleanup(ctx context.Context) {
	if globalTrashDedup == nil {
		return
	}

	ticker := time.NewTicker(trashDedupCleanupInterval)
	defer ticker.Stop()

	for {
		select {
		case <-ctx.Done():
			return
		case <-ticker.C:
			cleanupTrashDedup()
		}
	}
}

func cleanupTrashDedup() {
	if globalTrashDedup == nil {
		return
	}

	globalTrashDedup.mu.Lock()
	defer globalTrashDedup.mu.Unlock()

	now := time.Now()
	for key, entry := range globalTrashDedup.entries {
		if now.Sub(entry.lastStore) > trashDedupTTL {
			delete(globalTrashDedup.entries, key)
		}
	}

	log.Trace().Int("groups_tracked", len(globalTrashDedup.entries)).Msg("trash dedup cleanup cycle")
}
