package limiter

import (
	"fmt"
	"time"

	"limiter/pkg/model"
)

func (mt *messageTimer) addGroupTicker(groupKey string, groupInterval, groupLimit int) {
	item, ok, _ := mt.groups.Get(groupKey)
	if !ok {
		mt.logger.Debug(fmt.Sprintf("Group ticker for key '%s' not exist", groupKey), nil)
		return
	}

	_, ok = mt.groups.GroupsTimers.Load(groupKey)
	if ok {
		mt.logger.Error(fmt.Sprintf("try add new group timers for exist key %s", groupKey), nil)
		return
	}

	go func() {
		for {
			select {
			case t := <-item.Ticker.C:
				mt.logger.Debug(fmt.Sprintf("Tick for group key: '%s' at: %s", groupKey, t), nil)
				mt.groups.GroupsTimers.Store(groupKey, time.Now().UTC())
				mt.logger.Info(fmt.Sprintf("Added group ticker for key '%s'", groupKey), nil)

				mt.groups.HandleActiveGroupCustomers(groupKey, time.Now().UTC())
				exists, err := mt.getSavedGroupMessages(groupKey, groupLimit*2)
				if exists > 0 || err != nil {
					mt.logger.Debug(fmt.Sprintf("In group key '%s' found %d messages", groupKey, exists), nil)
					continue
				}

				mt.logger.Debug(fmt.Sprintf("Remove ticker '%s' due empty customers", groupKey), nil)
				item.Ticker.Stop()
				mt.groups.Delete(groupKey)
				mt.groups.GroupsTimers.Delete(groupKey)
				mt.logger.Debug(fmt.Sprintf("Removed group ticker for key '%s'", groupKey), nil)
				return
			}
		}
	}()
}

func (mt *messageTimer) canGroupHandle(groupData *model.RequestGroup, key string, count int) bool {
	if groupData == nil {
		return true
	}

	sum, empty := mt.groups.GetMessagesInGroup(groupData.Key)

	if empty {
		return true
	}

	return sum < (groupData.Count)
}

func (mt *messageTimer) getSavedGroupMessages(groupKey string, limit int) (int, error) {
	return mt.storage.CountInGroup([]string{groupKey}, limit)
}
