package storage

import (
	"gopkg.in/mgo.v2/bson"
)

type CachedStorage struct {
	db    Storage
	cache map[string]int
}

// Returns the pointer to new created mongo storage instance
func NewCachedMongo(db Storage) (*CachedStorage) {
	return &CachedStorage{db, make(map[string]int, 0)}
}

func (cm *CachedStorage) Get(key string, length int) ([]*Message, error) {
	return cm.db.Get(key, length)
}

func (cm *CachedStorage) Count(key string) (int, error) {
	_, ok := cm.cache[key]
	if ok {
		return cm.cache[key], nil
	}

	num, err := cm.db.Count(key)
	if err != nil {
		return 0, err
	}

	// TODO - how to delete unused keys? (start ticker to find 0 values and delete them?)
	cm.cache[key] = num

	return num, nil
}

func (cm *CachedStorage) GetDistinctFirstItems() (map[string]*Message, error) {
	return cm.db.GetDistinctFirstItems()
}

func (cm *CachedStorage) Exists(key string) (bool, error) {
	num, err := cm.Count(key)
	if err != nil {
		return false, err
	}

	return num > 0, nil
}

// Save persists document to mongo and then increases local counter for particular key
func (cm *CachedStorage) Save(m *Message) (string, error) {
	_, err := cm.db.Save(m)
	if err != nil {
		return m.LimitKey, err
	}

	cm.increaseCount(m.LimitKey)

	return m.LimitKey, nil
}

func (cm *CachedStorage) Remove(key string, id bson.ObjectId) (bool, error) {
	_, err := cm.db.Remove(key, id)
	if err != nil {
		return false, err
	}

	cm.decreaseCount(key)
	if cm.getCount(key) == 0 {
		delete(cm.cache, key)
	}

	return true, nil
}

func (cm *CachedStorage) increaseCount(key string) {
	cm.cache[key] = cm.getCount(key) + 1
}

func (cm *CachedStorage) decreaseCount(key string) {
	cm.cache[key] = cm.getCount(key) - 1
}

func (cm *CachedStorage) getCount(key string) int {
	val, ok := cm.cache[key]
	if !ok {
		return 0
	}

	return val
}
