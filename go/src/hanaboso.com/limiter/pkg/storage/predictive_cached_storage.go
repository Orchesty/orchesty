package storage

import (
	"gopkg.in/mgo.v2/bson"
	"time"
	"hanaboso.com/limiter/pkg/logger"
	"fmt"
)

type predictiveCachedStorage struct {
	db     Storage
	cache  map[string]cacheItem
	logger logger.Logger
}

type cacheItem struct {
	ticker *time.Ticker
	max    int
	count  int
}

// Returns the pointer to new created mongo storage instance
func NewPredictiveCachedStorage(db Storage, duration time.Duration, logger logger.Logger) (*predictiveCachedStorage) {
	c := predictiveCachedStorage{db, make(map[string]cacheItem, 100), logger}
	c.runCacheAutoClean(duration)

	return &c
}

func (cm *predictiveCachedStorage) Get(key string, length int) ([]*Message, error) {
	return cm.db.Get(key, length)
}

// GetDistinctFirstItems returns first message for every distinct key in storage
func (cm *predictiveCachedStorage) GetDistinctFirstItems() (map[string]*Message, error) {
	return cm.db.GetDistinctFirstItems()
}

// Save persists document to mongo. Increases of cache counter should have already been done in Check
func (cm *predictiveCachedStorage) Save(m *Message) (string, error) {
	return cm.db.Save(m)
}

// Remove tries to delete the concrete message from storage
func (cm *predictiveCachedStorage) Remove(key string, id bson.ObjectId) (bool, error) {
	return cm.db.Remove(key, id)
}

// Check decides whether the message can be processed
func (cm *predictiveCachedStorage) CanHandle(key string, interval int, value int) (bool, error) {
	item, isNew := cm.getCachedItem(key)
	if isNew {
		item.max = value

		item.ticker = time.NewTicker(time.Second * time.Duration(interval))
		go func() {
			for range item.ticker.C {
				i, _ := cm.getCachedItem(key)
				i.count = i.count - i.max

				if i.count > 0 {
					cm.saveCachedItem(key, i)
					continue
				}

				if i.ticker != nil {
					i.ticker.Stop()
				}

				delete(cm.cache, key)
			}
		}()
	}

	item.count++
	cm.saveCachedItem(key, item)

	return item.count <= item.max, nil
}

// Count return the amount of messages with given key in storage
func (cm *predictiveCachedStorage) Count(key string) (int, error) {
	i, _ := cm.getCachedItem(key)

	return i.count, nil
}

func (cm *predictiveCachedStorage) Exists(key string) (bool, error) {
	num, err := cm.Count(key)
	if err != nil {
		return false, err
	}

	return num > 0, nil
}

func (cm *predictiveCachedStorage) hasCachedItem(key string) bool {
	_, ok := cm.cache[key]

	return ok
}

// getCachedItem returns cachedItem for given key. New is set to true if cachedItem was created
func (cm *predictiveCachedStorage) getCachedItem(key string) (cacheItem, bool) {
	item, ok := cm.cache[key]
	if ok {
		return item, false
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
func (cm *predictiveCachedStorage) saveCachedItem(key string, item cacheItem) {
	cm.cache[key] = item
}

// runCacheAutoClean starts ticker for cleaning cache
func (cm *predictiveCachedStorage) runCacheAutoClean(duration time.Duration) {
	cleanTick := time.NewTicker(duration)
	go func() {
		for range cleanTick.C {
			cm.logger.Warning(fmt.Sprintf("Cleaning predictive cache %sx items...", len(cm.cache)), nil)
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
