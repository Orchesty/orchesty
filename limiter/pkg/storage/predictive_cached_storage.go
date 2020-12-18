package storage

import (
	"fmt"
	"sync"
	"time"

	"gopkg.in/mgo.v2/bson"

	"limiter/pkg/logger"
)

type cacheHolder struct {
	Cache sync.Map
}

func (ch *cacheHolder) get(key string) (cacheItem, bool) {
	item, ok := ch.Cache.Load(key)
	if !ok {
		return cacheItem{}, false
	}

	value, ok := item.(cacheItem)
	if !ok {
		return cacheItem{}, false
	}

	return value, true
}

func (ch *cacheHolder) save(key string, value cacheItem) {
	ch.Cache.Store(key, value)
}

func (ch *cacheHolder) delete(key string) {
	ch.Cache.Delete(key)
}

func (ch *cacheHolder) len() int {
	var length int
	ch.Cache.Range(func(_, _ interface{}) bool {
		length++

		return true
	})

	return length
}

// PredictiveCachedStorage is storage that uses cache for easing the DB
type PredictiveCachedStorage struct {
	db       Storage
	cache    map[string]cacheItem
	newCache *cacheHolder
	logger   logger.Logger
	m        sync.Mutex
}

type cacheItem struct {
	ticker *time.Ticker
	max    int
	count  int
}

// NewPredictiveCachedStorage returns the pointer to new created mongo storage instance
func NewPredictiveCachedStorage(db Storage, duration time.Duration, logger logger.Logger) *PredictiveCachedStorage {
	cache := &cacheHolder{}
	c := PredictiveCachedStorage{db, make(map[string]cacheItem, 100), cache, logger, sync.Mutex{}}
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
	item, ok := cm.newCache.get(key)
	if !ok {
		return false
	}
	item.count = 0
	cm.newCache.save(key, item)

	return true
}

func (cm *PredictiveCachedStorage) canHandleTicker(t <-chan time.Time, key string) {
	for range t {
		tt := <-t
		cm.logger.Log("debug", fmt.Sprintf("Handle tick for key: '%s' at: %s", key, tt.Format("2006-Jan-2 15:04:05")), nil)
		i, _, err := cm.getCachedItem(key)

		if err != nil {
			continue
		}

		i.count, err = cm.db.Count(key)
		if err != nil {
			cm.logger.Error(fmt.Sprintf("ERROR => %v", err), nil)
			continue
		}

		i.count = i.count - i.max
		if i.count > 0 {
			cm.saveCachedItem(key, i)
			continue
		}

		if i.ticker != nil {
			cm.logger.Info(fmt.Sprintf("Remove ticker for key %s", key), nil)
			i.ticker.Stop()
		}
		cm.newCache.delete(key)
	}
}

// CanHandle decides whether the message can be processed
func (cm *PredictiveCachedStorage) CanHandle(key string, interval int, value int) (bool, error) {
	item, isNew, err := cm.getCachedItem(key)

	if err != nil {
		return false, fmt.Errorf("failed get cachedItem %s => %v", key, err)
	}

	if isNew || item.ticker == nil {
		item.max = value

		item.ticker = time.NewTicker(time.Second * time.Duration(interval))
		go cm.canHandleTicker(item.ticker.C, key)
	}

	if item.ticker == nil {
		return false, nil
	}

	item.count++
	cm.saveCachedItem(key, item)

	return item.count <= item.max, nil
}

// Count return the amount of messages with given key in storage
func (cm *PredictiveCachedStorage) Count(key string) (int, error) {
	_, _, err := cm.getCachedItem(key)

	if err != nil {
		return 0, err
	}

	currentCount, err := cm.db.Count(key)
	if err != nil {
		cm.logger.Error(fmt.Sprintf("ERROR => %v", err), nil)
		return 0, err
	}

	return currentCount, nil
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
	_, ok := cm.newCache.get(key)

	return ok
}

// getCachedItem returns cachedItem for given key. New is set to true if cachedItem was created
func (cm *PredictiveCachedStorage) getCachedItem(key string) (cacheItem, bool, error) {
	item, ok := cm.newCache.get(key)
	if ok {
		return item, false, nil
	}

	return cacheItem{}, true, nil
}

// saveCachedItem saves the item struct to memory
func (cm *PredictiveCachedStorage) saveCachedItem(key string, item cacheItem) {
	cm.newCache.save(key, item)
}

func (cm *PredictiveCachedStorage) handler(key, value interface{}) bool {
	k, ok := key.(string)
	if !ok {
		return false
	}

	item, ok := value.(cacheItem)
	if !ok {
		return false
	}

	if item.ticker != nil {
		item.ticker.Stop()
		cm.logger.Info("Cleaning predictive cache - "+k, nil)
	} else {
		cm.logger.Warning("Cleaning predictive cache - "+k+" (no ticker)", nil)
	}

	return true
}

// runCacheAutoClean starts ticker for cleaning cache
func (cm *PredictiveCachedStorage) runCacheAutoClean(duration time.Duration) {
	cleanTick := time.NewTicker(duration)
	go func() {
		for range cleanTick.C {
			cm.logger.Warning(fmt.Sprintf("Cleaning predictive cache %dx items...", cm.newCache.len()), nil)
			cm.newCache.Cache.Range(cm.handler)
			cm.logger.Warning("Cleaning predictive cache ended.", nil)

			//TODO: proc to sem tomas dal :D, musim proverit bezpecnost
			cm.newCache = &cacheHolder{}
		}
	}()
}
