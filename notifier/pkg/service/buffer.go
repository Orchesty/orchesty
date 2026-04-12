package service

import (
	"context"
	"encoding/json"
	"fmt"
	"strings"
	"sync"
	"time"

	"github.com/redis/go-redis/v9"

	"notifier/pkg/config"
	"notifier/pkg/model"
)

type (
	BufferEntry struct {
		NodeName     string
		ErrorMessage string
	}

	BufferData struct {
		FirstEvent model.EventEnvelope
		Entries    []BufferEntry
	}

	BufferService interface {
		Add(ctx context.Context, key string, entry BufferEntry, firstEvent model.EventEnvelope) (isNew bool, err error)
		Flush(key string) (*BufferData, error)
	}
)

func entryToString(e BufferEntry) string {
	return e.NodeName + "|" + e.ErrorMessage
}

func stringToEntry(s string) BufferEntry {
	parts := strings.SplitN(s, "|", 2)
	if len(parts) == 2 {
		return BufferEntry{NodeName: parts[0], ErrorMessage: parts[1]}
	}

	return BufferEntry{NodeName: s}
}

func eventsKey(key string) string { return "buffer:events:" + key }
func metaKey(key string) string   { return "buffer:meta:" + key }

// --- Redis implementation ---

type RedisBuffer struct {
	client *redis.Client
}

func NewRedisBuffer(client *redis.Client) *RedisBuffer {
	return &RedisBuffer{client: client}
}

func (b *RedisBuffer) Add(ctx context.Context, key string, entry BufferEntry, firstEvent model.EventEnvelope) (bool, error) {
	ek := eventsKey(key)
	mk := metaKey(key)

	added, err := b.client.SAdd(ctx, ek, entryToString(entry)).Result()
	if err != nil {
		return false, fmt.Errorf("buffer SADD failed: %w", err)
	}

	exists, err := b.client.Exists(ctx, mk).Result()
	if err != nil {
		return false, fmt.Errorf("buffer EXISTS failed: %w", err)
	}

	isNew := exists == 0
	if isNew {
		meta, err := json.Marshal(firstEvent)
		if err != nil {
			return false, fmt.Errorf("buffer meta marshal failed: %w", err)
		}

		ttl := time.Duration(config.Throttle.BufferWindowMs+5000) * time.Millisecond
		b.client.Set(ctx, mk, meta, ttl)
		b.client.Expire(ctx, ek, ttl)
	}

	_ = added

	return isNew, nil
}

func (b *RedisBuffer) Flush(key string) (*BufferData, error) {
	ctx := context.Background()
	ek := eventsKey(key)
	mk := metaKey(key)

	members, err := b.client.SMembers(ctx, ek).Result()
	if err != nil {
		return nil, fmt.Errorf("buffer SMEMBERS failed: %w", err)
	}

	metaJSON, err := b.client.Get(ctx, mk).Result()
	if err != nil && err != redis.Nil {
		return nil, fmt.Errorf("buffer GET meta failed: %w", err)
	}

	b.client.Del(ctx, ek, mk)

	if len(members) == 0 || metaJSON == "" {
		return nil, nil
	}

	var firstEvent model.EventEnvelope
	if err := json.Unmarshal([]byte(metaJSON), &firstEvent); err != nil {
		return nil, fmt.Errorf("buffer meta unmarshal failed: %w", err)
	}

	entries := make([]BufferEntry, len(members))
	for i, m := range members {
		entries[i] = stringToEntry(m)
	}

	return &BufferData{FirstEvent: firstEvent, Entries: entries}, nil
}

// --- Memory implementation ---

type memoryBufferItem struct {
	firstEvent model.EventEnvelope
	entries    map[string]BufferEntry
}

type MemoryBuffer struct {
	mu    sync.Mutex
	items map[string]*memoryBufferItem
}

func NewMemoryBuffer() *MemoryBuffer {
	return &MemoryBuffer{items: make(map[string]*memoryBufferItem)}
}

func (b *MemoryBuffer) Add(_ context.Context, key string, entry BufferEntry, firstEvent model.EventEnvelope) (bool, error) {
	b.mu.Lock()
	defer b.mu.Unlock()

	item, exists := b.items[key]
	if !exists {
		item = &memoryBufferItem{
			firstEvent: firstEvent,
			entries:    make(map[string]BufferEntry),
		}
		b.items[key] = item
	}

	item.entries[entryToString(entry)] = entry

	return !exists, nil
}

func (b *MemoryBuffer) Flush(key string) (*BufferData, error) {
	b.mu.Lock()
	defer b.mu.Unlock()

	item, exists := b.items[key]
	if !exists {
		return nil, nil
	}

	delete(b.items, key)

	entries := make([]BufferEntry, 0, len(item.entries))
	for _, e := range item.entries {
		entries = append(entries, e)
	}

	return &BufferData{FirstEvent: item.firstEvent, Entries: entries}, nil
}

func NewBufferService(redisClient *redis.Client) BufferService {
	if redisClient != nil {
		config.Logger.Info("Using Redis buffer store")

		return NewRedisBuffer(redisClient)
	}

	config.Logger.Info("Using in-memory buffer store")

	return NewMemoryBuffer()
}
