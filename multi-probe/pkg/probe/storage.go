package probe

import "multi-probe/pkg/config"

const (
	StorageType_Redis  = "redis"
	StorageType_Memory = "memory"
)

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

func GetStorage() Storage {
	switch config.Storage.Type {
	case StorageType_Redis:
		return newRedisStorage()
	default:
		return newMemoryStorage()
	}
}
