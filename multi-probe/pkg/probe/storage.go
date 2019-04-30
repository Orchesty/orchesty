package probe

import "github.com/go-redis/redis"

type Storage interface {
	// Set adds the value to storage
	Set(key string, value []byte) error
	// Get returns the value from storage or returns error
	Get(key string) (string, error)
	// Delete removes value from storage
	Delete(key string) error
	// Keys returns all keys in storage
	Keys() ([]string, error)
}

// Redis is Redis storage implementation for probe
type RedisStorage struct {
	Client *redis.Client
}

const RedisKey = "multi-probe"

func (r *RedisStorage) Set(key string, value []byte) error {
	return r.Client.HSet(RedisKey, key, value).Err()
}

func (r *RedisStorage) Get(key string) (string, error) {
	return r.Client.HGet(RedisKey, key).Result()
}

func (r *RedisStorage) Delete(key string) error {
	var _, err = r.Client.HDel(RedisKey, key).Result()

	return err
}

func (r *RedisStorage) Keys() ([]string, error) {
	return r.Client.HKeys(RedisKey).Result()
}
