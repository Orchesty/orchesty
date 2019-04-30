package storage

import (
	"gopkg.in/mgo.v2/bson"
)

// CachedStorage represents a storage that uses cache
type CachedStorage struct {
	db    Storage
	cache map[string]int
}

// NewCachedMongo returns the pointer to new created mongo storage instance
func NewCachedMongo(db Storage) *CachedStorage {
	return &CachedStorage{db, make(map[string]int, 0)}
}

// Get returns the message form storage
func (cs *CachedStorage) Get(key string, length int) ([]*Message, error) {
	return cs.db.Get(key, length)
}

// Count returns the number of items in storage by key
func (cs *CachedStorage) Count(key string) (int, error) {
	_, ok := cs.cache[key]
	if ok {
		return cs.cache[key], nil
	}

	num, err := cs.db.Count(key)
	if err != nil {
		return 0, err
	}

	// TODO - how to delete unused keys? (start ticker to find 0 values and delete them?)
	cs.cache[key] = num

	return num, nil
}

// GetDistinctFirstItems returns distinct messages
func (cs *CachedStorage) GetDistinctFirstItems() (map[string]*Message, error) {
	return cs.db.GetDistinctFirstItems()
}

// Exists returns whether key exists in storage
func (cs *CachedStorage) Exists(key string) (bool, error) {
	num, err := cs.Count(key)
	if err != nil {
		return false, err
	}

	return num > 0, nil
}

// Save persists document to mongo and then increases local counter for particular key
func (cs *CachedStorage) Save(m *Message) (string, error) {
	_, err := cs.db.Save(m)
	if err != nil {
		return m.LimitKey, err
	}

	cs.increaseCount(m.LimitKey)

	return m.LimitKey, nil
}

// Remove deletes a record in storage
func (cs *CachedStorage) Remove(key string, id bson.ObjectId) (bool, error) {
	_, err := cs.db.Remove(key, id)
	if err != nil {
		return false, err
	}

	cs.decreaseCount(key)
	if cs.getCount(key) == 0 {
		delete(cs.cache, key)
	}

	return true, nil
}

func (cs *CachedStorage) increaseCount(key string) {
	cs.cache[key] = cs.getCount(key) + 1
}

func (cs *CachedStorage) decreaseCount(key string) {
	cs.cache[key] = cs.getCount(key) - 1
}

func (cs *CachedStorage) getCount(key string) int {
	val, ok := cs.cache[key]
	if !ok {
		return 0
	}

	return val
}
