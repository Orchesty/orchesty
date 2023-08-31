package limiter

import (
	"fmt"
	"github.com/hanaboso/go-utils/pkg/arrayx"
	"github.com/hanaboso/go-utils/pkg/intx"
	"github.com/rs/zerolog/log"
	"strings"
	"sync"
	"time"
)

type Cache struct {
	activeKeys map[string]CacheItem
	lock       *sync.Mutex
}

type CacheItem struct {
	Keys       []string
	Amount     int
	LastAmount int
	Increased  int
}

func (this *Cache) RegisterKey(key string) {
	this.RegisterKeyAmount(key, 1)
}

func (this *Cache) RemoveKey(key string) {
	if key == "" {
		return
	}

	this.lock.Lock()
	delete(this.activeKeys, key)
	this.lock.Unlock()
}

func (this *Cache) FinishProcess(key string) {
	this.RegisterKeyAmount(key, -1)
}

func (this *Cache) RegisterKeyAmount(key string, amount int) {
	this.lock.Lock()
	item, ok := this.activeKeys[key]
	if !ok {
		item = CacheItem{
			Keys:   arrayx.NthItemsFrom(strings.Split(key, ";"), 3, 0),
			Amount: intx.Max(0, amount),
		}
	} else {
		item.Amount = intx.Max(0, item.Amount+amount)
	}

	if item.Amount > 0 {
		this.activeKeys[key] = item
	} else {
		delete(this.activeKeys, key)
	}

	this.lock.Unlock()
}

func (this *Cache) NextItems() map[string]CacheItem {
	this.lock.Lock()
	defer this.lock.Unlock()

	keys := map[string]CacheItem{}
	for key, item := range this.activeKeys {
		if item.Amount > 0 {
			keys[key] = item
		}
	}

	return keys
}

func (this *Cache) FromLimits(limits map[string]int) {
	for key, amount := range limits {
		this.RegisterKeyAmount(key, amount)
	}
}

func (this *Cache) startWatcher() {
	for range time.Tick(time.Minute) {
		this.lock.Lock()
		for key, limit := range this.activeKeys {
			if limit.Amount > limit.LastAmount {
				limit.Increased++
				if limit.Increased > 10 {
					limit.Increased = 0
					log.Warn().Str("limiter-key", key).Err(fmt.Errorf("messages with limit key are stacking up")).Send()
				}
			} else {
				limit.Increased = 0
			}

			limit.LastAmount = limit.Amount
			this.activeKeys[key] = limit
		}
		this.lock.Unlock()
	}
}

func NewCache() *Cache {
	svc := &Cache{
		activeKeys: map[string]CacheItem{},
		lock:       &sync.Mutex{},
	}
	svc.activeKeys[""] = CacheItem{
		Keys:   []string{""},
		Amount: 0,
	}

	return svc
}
