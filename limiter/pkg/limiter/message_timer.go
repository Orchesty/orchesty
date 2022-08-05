package limiter

import (
	"fmt"
	"limiter/pkg/utils/intx"
	"time"

	"limiter/pkg/logger"
	"limiter/pkg/model"
	"limiter/pkg/rabbitmq"
	"limiter/pkg/storage"
)

// MessageTimer represents the timer that checks stored messages if they can become free
type MessageTimer interface {
	Init()
}

type messageTimer struct {
	storage   storage.Storage
	publisher rabbitmq.Publisher
	timerChan chan *storage.Message
	logger    logger.Logger
	groups    model.GroupCache
	customers model.CustomerCache
}

// NewMessageTimer return a new MessageTimer instance
func NewMessageTimer(
	s storage.Storage,
	p rabbitmq.Publisher,
	timerChan chan *storage.Message,
	logger logger.Logger,
) MessageTimer {
	return &messageTimer{
		storage:   s,
		publisher: p,
		timerChan: timerChan,
		logger:    logger,
		groups:    model.GroupCache{},
		customers: model.CustomerCache{},
	}
}

// Init loads and sets timers for already persisted messages and starts new timers handler
func (mt *messageTimer) Init() {
	mt.loadExistingGroupTimers()
	mt.loadExistingTimers()
	go mt.startHandleNewTimers()
	go mt.startTimersGuard()
}

func (mt *messageTimer) addTicker(key string, duration int, count int, groupData *model.RequestGroup) {
	item, ok, _ := mt.customers.Get(key)
	if !ok {
		return
	}

	mt.logger.Debug(fmt.Sprintf("Added ticker for key '%s'", key), nil)
	go func() {
		for t := range item.Ticker.C {
			mt.logger.Debug(fmt.Sprintf("Tick for key: '%s' at: %s", key, t), nil)

			validGroup := mt.canGroupHandle(groupData, key, count)
			if !validGroup {
				mt.logger.Debug(fmt.Sprintf("NOT ALLOW handle group: '%s' for base key: '%s'", groupData.Key, key), nil)
				continue
			}

			if groupData != nil {
				c, ok, _ := mt.groups.GetCustomer(key)
				if !ok {
					mt.groups.SaveCustomer(key, model.LevelOne{
						Group:    groupData.Key,
						Interval: duration,
						Valid:    time.Now().UTC().Add(time.Second * time.Duration(duration)),
						Count:    count,
					})
				} else {
					c.Valid = time.Now().UTC().Add(time.Second * time.Duration(duration))
					c.Count += count
					mt.groups.SaveCustomer(key, c)
				}
			}

			hasNext := mt.release(key, count)
			if !hasNext {
				item.Ticker.Stop()
				mt.customers.Delete(key)
				mt.logger.Debug(fmt.Sprintf("Removed ticker for key '%s'", key), nil)
				return
			}
		}
	}()
}

func (mt *messageTimer) release(key string, count int) bool {
	toRelease := count
	exists := false

	for toRelease > 0 {
		currentCount := intx.Min(toRelease, 50)
		toRelease -= currentCount
		msgs, err := mt.storage.Get(key, currentCount)

		if err != nil {
			mt.logger.Error(fmt.Sprintf("Release could not get messages from storage. Error: %s", err), logger.Context{"error": err})
			return true
		}

		for _, m := range msgs {
			mt.publisher.SetRoutingKey(m.ReturnRoutingKey)
			mt.publisher.SetExchange(m.ReturnExchange)
			mt.publisher.Publish(m.Message)
			mt.deleteMessage(m)
		}

		if toRelease > 0 {
			continue
		}

		if msgs == nil {
			mt.storage.ClearCacheItem(key, 0)
		} else {
			//todo: proc kdyz je v mongu nic stale se kouka na kes
			exists, err = mt.storage.Exists(key)

			if err != nil {
				mt.logger.Error(fmt.Sprintf("Release could not check if some messages exist for key %s Error: %s", key, err), logger.Context{"error": err})
				return true
			}
		}
	}

	return exists
}

func (mt *messageTimer) loadExistingGroupTimers() {
	items, err := mt.storage.GetDistinctGroupFirstItems()
	if err != nil {
		mt.logger.Error(fmt.Sprintf("Init group error: %v", err.Error()), nil)
	}

	for _, i := range items {
		mt.groups.Save(i.GroupKey, model.CacheItem{
			Ticker: time.NewTicker(time.Second * time.Duration(i.GroupTime)),
			Max:    i.GroupValue,
			Count:  0,
		})

		mt.addGroupTicker(i.GroupKey, i.GroupTime, i.GroupValue)
	}
}

func (mt *messageTimer) loadExistingTimers() {
	items, err := mt.storage.GetDistinctFirstItems()
	if err != nil {
		mt.logger.Error(fmt.Sprintf("Init error: %v", err.Error()), nil)
	}

	for _, i := range items {
		var rg *model.RequestGroup
		if i.GroupKey != "" {
			rg = &model.RequestGroup{
				Key:      i.GroupKey,
				Interval: i.GroupTime,
				Count:    i.GroupValue,
			}
			//
			//mt.groups.Save(i.GroupKey, model.CacheItem{
			//	Ticker: time.NewTicker(time.Second * time.Duration(i.GroupTime)),
			//	Max:    i.GroupValue,
			//	Count:  0,
			//})
			//
			//mt.addGroupTicker(i.GroupKey, i.GroupTime, i.GroupValue)
		}

		mt.customers.Save(i.LimitKey, model.CacheItem{
			Ticker: time.NewTicker(time.Second * time.Duration(i.LimitTime)),
			Max:    i.LimitValue,
			Count:  0,
		})

		mt.addTicker(i.LimitKey, i.LimitTime, i.LimitValue, rg)
	}
}

func (mt *messageTimer) startHandleNewTimers() {
	for m := range mt.timerChan {
		if m.GroupKey != "" {
			_, ok, _ := mt.groups.Get(m.GroupKey)
			if !ok {
				mt.groups.Save(m.GroupKey, model.CacheItem{
					Ticker: time.NewTicker(time.Second * time.Duration(m.GroupTime)),
					Max:    m.GroupValue,
					Count:  0,
				})
				mt.addGroupTicker(m.GroupKey, m.GroupTime, m.GroupValue)
			}
		}

		_, ok, _ := mt.customers.Get(m.LimitKey)
		if !ok {
			var rg *model.RequestGroup
			if m.GroupKey != "" {
				rg = &model.RequestGroup{
					Key:      m.GroupKey,
					Interval: m.GroupTime,
					Count:    m.GroupValue,
				}
			}

			mt.customers.Save(m.LimitKey, model.CacheItem{
				Ticker: time.NewTicker(time.Second * time.Duration(m.LimitTime)),
				Max:    m.LimitValue,
				Count:  0,
			})

			mt.logger.Debug(fmt.Sprintf("Add ticker for key %s", m.LimitKey), nil)
			mt.addTicker(m.LimitKey, m.LimitTime, m.LimitValue, rg)
		}
	}
}

// deleteMessage removes message from storage or logs an error
func (mt *messageTimer) deleteMessage(m *storage.Message) {
	_, err := mt.storage.Remove(m.LimitKey, m.ID)

	if err != nil {
		mt.logger.Error(fmt.Sprintf("Message timer cannot delete message from storage. Error: %s", err), logger.Context{"error": err})
	}
}

func (mt *messageTimer) startTimersGuard() {
	tick := time.NewTicker(time.Minute)

	mt.logger.Info("START GUARD", nil)

	for range tick.C {
		items, err := mt.storage.GetDistinctFirstItems()
		mt.logger.Info("GET DISTINCT", nil)
		if err != nil {
			mt.logger.Error(fmt.Sprintf("Init error: %v", err.Error()), nil)
		}

		for _, i := range items {
			mt.logger.Info(fmt.Sprintf("ITEMS %s", i.LimitKey), nil)
			_, ok, _ := mt.customers.Get(i.LimitKey)
			if !ok {
				var rg *model.RequestGroup
				if i.GroupKey != "" {
					rg = &model.RequestGroup{
						Key:      i.GroupKey,
						Interval: i.GroupTime,
						Count:    i.GroupValue,
					}
				}

				mt.customers.Save(i.LimitKey, model.CacheItem{
					Ticker: time.NewTicker(time.Second * time.Duration(i.LimitTime)),
					Max:    i.LimitValue,
					Count:  0,
				})

				mt.addTicker(i.LimitKey, i.LimitTime, i.LimitValue, rg)
			}
		}
	}
}
