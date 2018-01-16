package storage

import "gopkg.in/mgo.v2/bson"

type CachedMongo struct {
	mongo *Mongo
	cache map[string]int
}

// Returns the pointer to new created mongo storage instance
func NewCachedMongo(mongo *Mongo) (*CachedMongo) {
	return &CachedMongo{mongo, make(map[string]int, 0)}
}

func (cm *CachedMongo) Get(key string, length int) ([]*Message, error) {
	return cm.mongo.Get(key, length)
}

func (cm *CachedMongo) GetDistinctFirstItems() (map[string]*Message, error) {
	return cm.mongo.GetDistinctFirstItems()
}

func (cm *CachedMongo) Check(key string, time int, value int) (bool, error) {
	exists, _ := cm.Exists(key)
	if exists == false {
		return true, nil
	}

	actual := cm.getCount(key)
	if actual > 0 {
		return false, nil
	}

	return true, nil
}

func (cm *CachedMongo) Exists(key string) (bool, error) {
	_, ok := cm.cache[key]
	if !ok {
		return false, nil
	}

	return true, nil
}

// Save persists document to mongo and then increases local counter for particular key
func (cm *CachedMongo) Save(m *Message) (string, error) {
	_, err := cm.mongo.Save(m)
	if err != nil {
		return m.LimitKey, err
	}

	cm.increaseCount(m.LimitKey)

	return m.LimitKey, nil
}

func (cm *CachedMongo) Remove(key string, id bson.ObjectId) (bool, error) {
	_, err := cm.mongo.Remove(key, id)
	if err != nil {
		return false, err
	}

	cm.decreaseCount(key)

	return true, nil
}

func (cm *CachedMongo) increaseCount(key string) int {
	cm.cache[key] = cm.getCount(key) + 1

	return cm.getCount(key) + 1
}

func (cm *CachedMongo) decreaseCount(key string) int {
	cm.cache[key] = cm.getCount(key) - 1

	return cm.getCount(key) - 1
}

func (cm *CachedMongo) getCount(key string) int {
	val, ok := cm.cache[key]
	if !ok {
		return 0
	}

	return val
}

