package model

import (
	"fmt"
	"sync"
	"time"
)

// GroupCache - holder for group limit
type GroupCache struct {
	Groups       sync.Map
	Customers    sync.Map
	GroupsTimers sync.Map
}

// LevelOne - holder fo customers
type LevelOne struct {
	Group    string
	Interval int
	Valid    time.Time
	Count    int
}

// CustomerCache - base cache item
type CustomerCache struct {
	Cache sync.Map
}

// CacheItem - holder for timers
type CacheItem struct {
	Ticker *time.Ticker
	Max    int
	Count  int
}

// Get - get CacheItem
func (cc *CustomerCache) Get(key string) (CacheItem, bool, error) {
	item, ok := cc.Cache.Load(key)
	if !ok {
		return CacheItem{}, false, nil
	}

	value, ok := item.(CacheItem)
	if !ok {
		return CacheItem{}, false, fmt.Errorf("cannot cast item to CacheItem")
	}

	return value, true, nil
}

// Save - save CacheItem
func (cc *CustomerCache) Save(key string, value CacheItem) {
	cc.Cache.Store(key, value)
}

// Delete - delete cache item
func (cc *CustomerCache) Delete(key string) {
	cc.Cache.Delete(key)
}

// GetCustomer - get cached cusomer level one
func (gc *GroupCache) GetCustomer(key string) (LevelOne, bool, error) {
	value, ok := gc.Customers.Load(key)
	if !ok {
		return LevelOne{}, false, nil
	}

	level, ok := value.(LevelOne)
	if !ok {
		return LevelOne{}, false, fmt.Errorf("cannot cast group item to LevelOne")
	}

	return level, true, nil
}

// SaveCustomer - holder item
func (gc *GroupCache) SaveCustomer(key string, group LevelOne) {
	gc.Customers.Store(key, group)
}

//DeleteCustomer - holder item
func (gc *GroupCache) DeleteCustomer(key string) {
	gc.Customers.Delete(key)
}

// GetMessagesInGroup - get released messages in group
func (gc *GroupCache) GetMessagesInGroup(groupKey string) (int, bool) {
	sum := 0
	empty := true
	gc.Customers.Range(func(key, value interface{}) bool {
		g, ok := value.(LevelOne)
		if ok {
			empty = false
			if g.Group == groupKey {
				//fmt.Printf("CUSTOMER: %s => count: %d\n", key, g.Count)
				sum += g.Count
			}
		}

		return true
	})

	return sum, empty
}

// HandleActiveGroupCustomers - handle active customer connections
func (gc *GroupCache) HandleActiveGroupCustomers(groupKey string, currentTime time.Time) {
	gc.Customers.Range(func(k, v interface{}) bool {
		value, ok := v.(LevelOne)
		if ok {
			if value.Group == groupKey {
				if value.Valid.Before(currentTime) {
					gc.DeleteCustomer(k.(string))
				} else {
					value.Count = 0
					gc.SaveCustomer(k.(string), value)
				}
			}
		}
		return true
	})
}

// Get holder item
func (gc *GroupCache) Get(key string) (CacheItem, bool, error) {
	value, ok := gc.Groups.Load(key)
	if !ok {
		return CacheItem{}, false, nil
	}

	groupInfo, ok := value.(CacheItem)
	if !ok {
		return CacheItem{}, false, fmt.Errorf("cannot cast group item to CacheItem")
	}

	return groupInfo, true, nil
}

// Save - holder item
func (gc *GroupCache) Save(key string, group CacheItem) {
	gc.Groups.Store(key, group)
}

//Delete - holder item
func (gc *GroupCache) Delete(key string) {
	gc.Groups.Delete(key)
}
