package probe

import (
	"github.com/go-redis/redis"
	"multi-probe/pkg/config"
)

// Redis is Redis storage implementation for probe
type redisStorage struct {
	Client *redis.Client
}

const RedisKey = "multi-probe"

func (r redisStorage) Set(key string, value []byte) error {
	return r.Client.HSet(RedisKey, key, value).Err()
}

func (r redisStorage) Get(key string) (string, error) {
	return r.Client.HGet(RedisKey, key).Result()
}

func (r redisStorage) Delete(key string) error {
	var _, err = r.Client.HDel(RedisKey, key).Result()

	return err
}

func (r redisStorage) Keys() ([]string, error) {
	return r.Client.HKeys(RedisKey).Result()
}

func newRedisStorage() redisStorage {
	host := config.Redis.Host
	port := config.Redis.Port
	pass := config.Redis.Password
	db := config.Redis.Db

	rCli := redis.NewClient(&redis.Options{
		Addr:     host + ":" + port,
		Password: pass,
		DB:       db,
	})

	return redisStorage{
		Client: rCli,
	}
}
