package storage

import (
	"fmt"
	"time"
)

type groupCache struct {
	*cacheItem
	Groups map[string]*customerInfo
}

func (g *groupCache) canHandle(key string, groupTime, childValue int) bool {
	if len(g.Groups) == 0 {
		return true
	}

	validGroup := false
	sum := 0
	for customerIdent, customerInfo := range g.Groups {
		sum += groupTime / customerInfo.interval * childValue
		if customerIdent == key && sum <= g.max {
			validGroup = true
		}
	}

	return validGroup
}

func (g *groupCache) handleRequest(key string, interval, groupValue int, t time.Time) {
	if _, ok := g.Groups[key]; !ok {
		g.Groups[key] = &customerInfo{
			interval: interval,
			count:    groupValue,
			last:     t,
		}
	} else {
		g.Groups[key].last = t
	}
}

type customerInfo struct {
	interval int
	count    int
	last     time.Time
}

func (cm *PredictiveCachedStorage) getCachedGroupItem(key string) (groupCache, bool, error) {
	item, ok, err := cm.newCache.getGroup(key)
	if err != nil {
		return groupCache{}, false, err
	}

	if ok {
		return item, false, nil
	}

	return groupCache{}, true, nil
}

func (cm *PredictiveCachedStorage) canHandleGroupTicker(item *groupCache, key string) {
	_, ok := cm.newCache.GroupTimers.Load(key)
	if ok {
		cm.logger.Error(fmt.Sprintf("try add new group timers for exist key %s", key), nil)
		return
	}

	for {
		select {
		case t := <-item.ticker.C:
			cm.logger.Debug(fmt.Sprintf("Handle tick for group key: '%s' at: %s", key, t.Format("2006-Jan-2 15:04:05")), nil)
			group, _, err := cm.getCachedGroupItem(key)

			if err != nil /*|| !ok*/ {
				cm.logger.Error(fmt.Sprintf("failed to get cached group item %s =>  %v", key, err), nil)
				return
			}

			cm.newCache.GroupTimers.Store(key, time.Now().UTC())
			if len(group.Groups) > 0 {
				for shop, item := range group.Groups {
					now := time.Now().UTC()
					after := item.last.Add(time.Duration(item.interval) * time.Second)
					if now.After(after) {
						delete(group.Groups, shop)
					}
				}
			}

			if len(group.Groups) > 0 {
				cm.newCache.saveGroup(key, group)
				continue
			}

			//TODO: poresit zda nenechat pustene i kdyz v DB neco je pak to nedopocitavat

			if group.ticker != nil {
				cm.logger.Info(fmt.Sprintf("Remove group ticker for key %s", key), nil)
				group.ticker.Stop()
			}

			cm.newCache.deleteGroup(key)
			cm.newCache.GroupTimers.Delete(key)
			return
		}
	}
}
