package probe

import (
	"errors"
	"github.com/patrickmn/go-cache"
	"time"
)

// Redis is Redis storage implementation for probe
type memoryStorage struct {
	c *cache.Cache
}

func (s memoryStorage) Set(key string, value []byte) error {
	s.c.Set(key, value, cache.NoExpiration)

	return nil
}

func (s memoryStorage) Get(key string) (string, error) {
	res, ok := s.c.Get(key)
	if !ok {
		return "", errors.New("not found")
	}

	return string(res.([]uint8)), nil
}

func (s memoryStorage) Delete(key string) error {
	s.c.Delete(key)

	return nil
}

func (s memoryStorage) Keys() ([]string, error) {
	items := s.c.Items()
	res := make([]string, len(items))
	c := 0
	for key := range items {
		res[c] = key
		c++
	}

	return res, nil
}

func newMemoryStorage() memoryStorage {
	return memoryStorage{
		c: cache.New(5*time.Minute, 10*time.Minute),
	}
}
