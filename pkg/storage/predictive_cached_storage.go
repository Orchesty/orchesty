package storage

import (
	"limiter/pkg/logger"

	"gopkg.in/mgo.v2/bson"

	"fmt"
	"time"
)

// PredictiveCachedStorage is storage that uses cache for easing the DB
type PredictiveCachedStorage struct {
	db     Storage
	cache  map[string]cacheItem
	logger logger.Logger
}

type cacheItem struct {
	ticker *time.Ticker
	max    int
	count  int
}

// NewPredictiveCachedStorage returns the pointer to new created mongo storage instance
func NewPredictiveCachedStorage(db Storage, duration time.Duration, logger logger.Logger) *PredictiveCachedStorage {
	c := PredictiveCachedStorage{db, make(map[string]cacheItem, 100), logger}
	c.runCacheAutoClean(duration)

	return &c
}

// Get returns the stored key
func (cm *PredictiveCachedStorage) Get(key string, length int) ([]*Message, error) {
	return cm.db.Get(key, length)
}

// GetDistinctFirstItems returns first message for every distinct key in storage
func (cm *PredictiveCachedStorage) GetDistinctFirstItems() (map[string]*Message, error) {
	return cm.db.GetDistinctFirstItems()
}

// Save persists document to mongo. Increases of cache counter should have already been done in Check
func (cm *PredictiveCachedStorage) Save(m *Message) (string, error) {
	return cm.db.Save(m)
}

// Remove tries to delete the concrete message from storage
func (cm *PredictiveCachedStorage) Remove(key string, id bson.ObjectId) (bool, error) {
	return cm.db.Remove(key, id)
}

// ClearCacheItem remove key from memory cache
func (cm *PredictiveCachedStorage) ClearCacheItem(key string, val int) bool {
	item, ok := cm.cache[key]
	if !ok {
		return false
	}
	item.count = 0
	cm.cache[key] = item

	return true
}

func (cm *PredictiveCachedStorage) canHandleTicker(t <-chan time.Time, key string) {
	for range t {
		cm.logger.Info(fmt.Sprintf("Handle tick for key: '%s' at: %v", key, t), nil)
		i, _ := cm.getCachedItem(key)
		i.count = i.count - i.max

		if i.count > 0 {
			cm.saveCachedItem(key, i)
			continue
		}

		if i.ticker != nil {
			cm.logger.Info(fmt.Sprintf("Remove ticker for key %s", key), nil)
			i.ticker.Stop()
		}

		delete(cm.cache, key)
	}
}

// CanHandle decides whether the message can be processed
func (cm *PredictiveCachedStorage) CanHandle(key string, interval int, value int) (bool, error) {
	item, isNew := cm.getCachedItem(key)
	if isNew {
		item.max = value

		item.ticker = time.NewTicker(time.Second * time.Duration(interval))
		go cm.canHandleTicker(item.ticker.C, key)
	}

	item.count++
	cm.saveCachedItem(key, item)

	return item.count <= item.max, nil
}

// Count return the amount of messages with given key in storage
func (cm *PredictiveCachedStorage) Count(key string) (int, error) {
	i, _ := cm.getCachedItem(key)

	return i.count, nil
}

// Exists returns whether the key exists or not in storage
func (cm *PredictiveCachedStorage) Exists(key string) (bool, error) {
	num, err := cm.Count(key)
	if err != nil {
		return false, err
	}

	return num > 0, nil
}

func (cm *PredictiveCachedStorage) hasCachedItem(key string) bool {
	_, ok := cm.cache[key]

	return ok
}

// getCachedItem returns cachedItem for given key. New is set to true if cachedItem was created
func (cm *PredictiveCachedStorage) getCachedItem(key string) (cacheItem, bool) {
	item, ok := cm.cache[key]
	if ok {
		if item.ticker != nil {
			return item, false
		}
	}

	item = cacheItem{}

	num, err := cm.db.Count(key)
	if err != nil {
		return item, true
	}

	item.count = num
	cm.saveCachedItem(key, item)

	return item, true
}

// saveCachedItem saves the item struct to memory
func (cm *PredictiveCachedStorage) saveCachedItem(key string, item cacheItem) {
	cm.cache[key] = item
}

// runCacheAutoClean starts ticker for cleaning cache
func (cm *PredictiveCachedStorage) runCacheAutoClean(duration time.Duration) {
	cleanTick := time.NewTicker(duration)
	go func() {
		for range cleanTick.C {
			cm.logger.Warning(fmt.Sprintf("Cleaning predictive cache %dx items...", len(cm.cache)), nil)
			for k, item := range cm.cache {
				if item.ticker != nil {
					item.ticker.Stop()
					cm.logger.Info("Cleaning predictive cache - "+k, nil)
				} else {
					cm.logger.Warning("Cleaning predictive cache - "+k+" (no ticker)", nil)
				}
			}

			cm.logger.Warning("Cleaning predictive cache ended.", nil)

			cm.cache = make(map[string]cacheItem, 100)
		}
	}()
}
