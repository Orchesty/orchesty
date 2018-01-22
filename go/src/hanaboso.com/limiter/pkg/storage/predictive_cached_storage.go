package storage

import (
	"gopkg.in/mgo.v2/bson"
	"time"
)

type predictiveCachedStorage struct {
	db    Storage
	cache map[string]cacheItem
}

type cacheItem struct {
	ticker *time.Ticker
	max    int
	count  int
}

// Returns the pointer to new created mongo storage instance
func NewPredictiveCachedStorage(db Storage) (*predictiveCachedStorage) {
	// TODO start invalidate cache tickers here
	return &predictiveCachedStorage{db, make(map[string]cacheItem, 0)}
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
		go func(cm *predictiveCachedStorage, key string) {
			for range item.ticker.C {
				//fmt.Println("tick")
				i, _ := cm.getCachedItem(key)
				//fmt.Println(i)
				i.count = i.count - i.max
				//fmt.Println(i)

				if i.count > 0 {
					cm.saveCachedItem(key, i)
					//fmt.Println("keeping tick")
					continue
				}

				if i.ticker != nil {
					//fmt.Println("deleting tick")
					i.ticker.Stop()
				}

				delete(cm.cache, key)
			}
		}(cm, key)
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

func (cm *predictiveCachedStorage) hasCachedItem(key string)  bool {
	_, ok := cm.cache[key]

	return ok
}

// getCachedItem returns cachedItem for given key. New is set to true if cachedItem was created
func (cm *predictiveCachedStorage) getCachedItem(key string)  (cacheItem, bool) {
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
