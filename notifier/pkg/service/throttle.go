package service

import (
	"context"
	"fmt"
	"sync"
	"time"

	"github.com/redis/go-redis/v9"
	"notifier/pkg/config"
)

type ThrottleStore interface {
	ThrottleOnce(ctx context.Context, key string, windowMs int) (bool, error)
	IsThrottled(ctx context.Context, key string) (bool, error)
	SetThrottle(ctx context.Context, key string, windowMs int) error
	Increment(ctx context.Context, key string, windowMs int) (int64, error)
	Ping(ctx context.Context) error
}

type RedisStore struct {
	client *redis.Client
}

func NewRedisStore(url string) (*RedisStore, error) {
	opts, err := redis.ParseURL(url)
	if err != nil {
		return nil, err
	}

	client := redis.NewClient(opts)
	if err := client.Ping(context.Background()).Err(); err != nil {
		return nil, err
	}

	return &RedisStore{client: client}, nil
}

func (s *RedisStore) ThrottleOnce(ctx context.Context, key string, windowMs int) (bool, error) {
	ok, err := s.client.SetNX(ctx, key, "1", time.Duration(windowMs)*time.Millisecond).Result()
	if err != nil {
		return false, err
	}

	return !ok, nil
}

func (s *RedisStore) IsThrottled(ctx context.Context, key string) (bool, error) {
	val, err := s.client.Exists(ctx, key).Result()
	if err != nil {
		return false, err
	}

	return val > 0, nil
}

func (s *RedisStore) SetThrottle(ctx context.Context, key string, windowMs int) error {
	return s.client.Set(ctx, key, "1", time.Duration(windowMs)*time.Millisecond).Err()
}

func (s *RedisStore) Increment(ctx context.Context, key string, windowMs int) (int64, error) {
	count, err := s.client.Incr(ctx, key).Result()
	if err != nil {
		return 0, err
	}

	if count == 1 {
		s.client.PExpire(ctx, key, time.Duration(windowMs)*time.Millisecond)
	}

	return count, nil
}

func (s *RedisStore) Ping(ctx context.Context) error {
	return s.client.Ping(ctx).Err()
}

func (s *RedisStore) Close() error {
	return s.client.Close()
}

func (s *RedisStore) Client() *redis.Client {
	return s.client
}

type MemoryStore struct {
	throttleMap sync.Map
	counterMap  sync.Map
}

func NewMemoryStore() *MemoryStore {
	return &MemoryStore{}
}

func (s *MemoryStore) ThrottleOnce(_ context.Context, key string, windowMs int) (bool, error) {
	if _, loaded := s.throttleMap.LoadOrStore(key, true); loaded {
		return true, nil
	}

	time.AfterFunc(time.Duration(windowMs)*time.Millisecond, func() {
		s.throttleMap.Delete(key)
	})

	return false, nil
}

func (s *MemoryStore) IsThrottled(_ context.Context, key string) (bool, error) {
	_, exists := s.throttleMap.Load(key)

	return exists, nil
}

func (s *MemoryStore) SetThrottle(_ context.Context, key string, windowMs int) error {
	s.throttleMap.Store(key, true)
	time.AfterFunc(time.Duration(windowMs)*time.Millisecond, func() {
		s.throttleMap.Delete(key)
	})

	return nil
}

func (s *MemoryStore) Ping(_ context.Context) error {
	return fmt.Errorf("redis not configured")
}

func (s *MemoryStore) Increment(_ context.Context, key string, windowMs int) (int64, error) {
	actual, loaded := s.counterMap.LoadOrStore(key, new(int64))
	counter := actual.(*int64)

	*counter++
	val := *counter

	if !loaded {
		time.AfterFunc(time.Duration(windowMs)*time.Millisecond, func() {
			s.counterMap.Delete(key)
		})
	}

	return val, nil
}

func NewThrottleStore() ThrottleStore {
	if config.Redis.URL != "" {
		store, err := NewRedisStore(config.Redis.URL)
		if err != nil {
			config.Logger.Error(err)
			config.Logger.Info("Falling back to in-memory throttle store")

			return NewMemoryStore()
		}

		config.Logger.Info("Using Redis throttle store")

		return store
	}

	config.Logger.Info("Using in-memory throttle store (no REDIS_DSN configured)")

	return NewMemoryStore()
}
