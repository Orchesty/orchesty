package storage

import (
	"fmt"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"sync"
	"time"

	"limiter/pkg/logger"
)

type socketTimer struct {
	*cacheItem
	stop <-chan bool
}

type cacheHolder struct {
	Cache       sync.Map
	Timers      sync.Map
	GroupCache  sync.Map
	GroupTimers sync.Map
}

func (ch *cacheHolder) get(key string) (socketTimer, bool, error) {
	item, ok := ch.Cache.Load(key)
	if !ok {
		return socketTimer{}, false, nil
	}

	value, ok := item.(socketTimer)
	if !ok {
		return socketTimer{}, false, fmt.Errorf("cannot cast item to socketTimer")
	}

	return value, true, nil
}

func (ch *cacheHolder) save(key string, value socketTimer) {
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

func (ch *cacheHolder) getGroup(key string) (groupCache, bool, error) {
	item, ok := ch.GroupCache.Load(key)
	if !ok {
		return groupCache{}, false, nil
	}

	value, ok := item.(groupCache)
	if !ok {
		return groupCache{}, false, fmt.Errorf("cannot cast item to groupCache")
	}

	return value, true, nil
}

func (ch *cacheHolder) saveGroup(key string, value groupCache) {
	ch.GroupCache.Store(key, value)
}

func (ch *cacheHolder) deleteGroup(key string) {
	ch.GroupCache.Delete(key)
}

func (ch *cacheHolder) lenGroup() int {
	var length int
	ch.GroupCache.Range(func(_, _ interface{}) bool {
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
	//groupLimit *groupLimit
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

// GetMessages return stored keys
func (cm *PredictiveCachedStorage) GetMessages(field, key string, length int) ([]*Message, error) {
	return cm.db.GetMessages(field, key, length)
}

// GetDistinctFirstItems returns first message for every distinct key in storage
func (cm *PredictiveCachedStorage) GetDistinctFirstItems() (map[string]*Message, error) {
	return cm.db.GetDistinctFirstItems()
}

// GetDistinctGroupFirstItems return all saved groups
func (cm *PredictiveCachedStorage) GetDistinctGroupFirstItems() (map[string]*Message, error) {
	return cm.db.GetDistinctGroupFirstItems()
}

// Save persists document to mongo. Increases of cache counter should have already been done in Check
func (cm *PredictiveCachedStorage) Save(m *Message) (string, error) {
	return cm.db.Save(m)
}

// CreateIndex - create mongo indexes
func (cm *PredictiveCachedStorage) CreateIndex(index mongo.IndexModel) error {
	return cm.db.CreateIndex(index)
}

// Remove tries to delete the concrete message from storage
func (cm *PredictiveCachedStorage) Remove(key string, id primitive.ObjectID) (bool, error) {
	return cm.db.Remove(key, id)
}

// ClearCacheItem remove key from memory cache
func (cm *PredictiveCachedStorage) ClearCacheItem(key string, _ int) bool {
	item, ok, _ := cm.newCache.get(key)
	if !ok {
		return false
	}
	item.count = 0
	cm.newCache.save(key, item)

	return true
}

func (cm *PredictiveCachedStorage) canHandleTicker(item *socketTimer, key string) {
	_, ok := cm.newCache.Timers.Load(key)
	if ok {
		cm.logger.Error(fmt.Sprintf("try add new timers for exist key %s", key), nil)
		return
	}

	// TODO: add context
	for {
		select {
		case t := <-item.ticker.C:
			cm.logger.Debug(fmt.Sprintf("Handle tick for key: '%s' at: %s", key, t.Format("2006-Jan-2 15:04:05")), nil)
			i, _, err := cm.getCachedItem(key)

			if err != nil /*|| !ok */ {
				cm.logger.Error(fmt.Sprintf("failed to get cached item %s =>  %v", key, err), nil)
				//cm.newCache.Timers.Delete(key)
				continue
			}

			count, err := cm.db.Count(key, item.max)
			if err != nil {
				cm.logger.Error(fmt.Sprintf("failed count saved messages in mongo for key item %s => %v", key, err), nil)
				continue
			}
			i.count = count

			cm.newCache.Timers.Store(key, time.Now().UTC())

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
			cm.newCache.Timers.Delete(key)
			return
		case <-item.stop:
			return
		}
	}
}

// CanHandle decides whether the message can be processed
func (cm *PredictiveCachedStorage) CanHandle(key string, interval int, value int, groupKey string, groupTime int, groupValue int) (bool, error) {
	cm.m.Lock()
	defer cm.m.Unlock()

	item, isNew, err := cm.getCachedItem(key)

	if err != nil {
		return false, fmt.Errorf("failed get cachedItem %s => %v", key, err)
	}

	if isNew {
		item = socketTimer{
			cacheItem: &cacheItem{
				ticker: time.NewTicker(time.Second * time.Duration(interval)),
				max:    value,
				count:  0,
			},
			stop: nil,
		}
		cm.logger.Debug(fmt.Sprintf("create new ticker for key %s", key), nil)
		go cm.canHandleTicker(&item, key)
	}

	item.count++
	cm.saveCachedItem(key, item)

	validGroup := true
	if groupKey != "" {
		itemGroup, isNewGroup, err := cm.getCachedGroupItem(groupKey)

		if err != nil {
			return false, fmt.Errorf("failed get groupCache %s => %v", groupKey, err)
		}

		if isNewGroup {
			itemGroup = groupCache{
				cacheItem: &cacheItem{
					ticker: time.NewTicker(time.Second * time.Duration(groupTime)),
					max:    groupValue,
					count:  0,
				},
				Groups: make(map[string]*customerInfo, 0),
			}
			cm.logger.Debug(fmt.Sprintf("create new group ticker for key %s", groupKey), nil)
			go cm.canHandleGroupTicker(&itemGroup, groupKey)
		}

		itemGroup.handleRequest(key, interval, groupValue, time.Now().UTC())
		cm.saveCachedGroupItem(groupKey, itemGroup)
		validGroup = itemGroup.canHandle(key, groupTime, value)
	}

	return item.count <= item.max && validGroup, nil
}

// Count return the amount of messages with given key in storage
func (cm *PredictiveCachedStorage) Count(key string, limit int) (int, error) {
	_, _, err := cm.getCachedItem(key)

	if err != nil {
		return 0, err
	}

	currentCount, err := cm.db.Count(key, limit)
	if err != nil {
		cm.logger.Error(fmt.Sprintf("ERROR => %v", err), nil)
		return 0, err
	}

	return currentCount, nil
}

// CountInGroup get group count
func (cm *PredictiveCachedStorage) CountInGroup(keys []string, limit int) (int, error) {
	return cm.db.CountInGroup(keys, limit)
}

// Exists returns whether the key exists or not in storage
func (cm *PredictiveCachedStorage) Exists(key string) (bool, error) {
	num, err := cm.Count(key, 1)
	if err != nil {
		return false, err
	}

	return num > 0, nil
}

func (cm *PredictiveCachedStorage) hasCachedItem(key string) bool {
	_, ok, _ := cm.newCache.get(key)

	return ok
}

// getCachedItem returns cachedItem for given key. New is set to true if cachedItem was created
func (cm *PredictiveCachedStorage) getCachedItem(key string) (socketTimer, bool, error) {
	item, ok, err := cm.newCache.get(key)
	if err != nil {
		return socketTimer{}, false, err
	}

	if ok {
		return item, false, nil
	}

	return socketTimer{}, true, nil
}

// saveCachedItem saves the item struct to memory
func (cm *PredictiveCachedStorage) saveCachedItem(key string, item socketTimer) {
	cm.newCache.save(key, item)
}

func (cm *PredictiveCachedStorage) saveCachedGroupItem(key string, group groupCache) {
	cm.newCache.saveGroup(key, group)
}

func (cm *PredictiveCachedStorage) handler(key, value interface{}) bool {
	k, ok := key.(string)
	if !ok {
		return false
	}

	item, ok := value.(socketTimer)
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
