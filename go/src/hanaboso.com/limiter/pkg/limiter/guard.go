package limiter

import (
	"time"
	"hanaboso.com/limiter/pkg/storage"
	"hanaboso.com/limiter/pkg/logger"
)

type Guard interface {
	IsOnBlacklist(key string) bool
	Check(tooOldDuration time.Duration)
}

type limitGuard struct {
	blacklist map[string]bool
	finder    storage.DistinctFinder
	logger    logger.Logger
}

func NewLimitGuard(finder storage.DistinctFinder, logger logger.Logger) *limitGuard {
	return &limitGuard{make(map[string]bool, 10), finder, logger}
}

// IsOnBlacklist returns whether the key is currently blacklisted or not
func (lg *limitGuard) IsOnBlacklist(key string) bool {
	_, ok := lg.blacklist[key]

	return ok
}

// Check updates the internal blacklist by the keys, that hold messages more then for tooOldDuration
func (lg *limitGuard) Check(tooOldDuration time.Duration) {
	lg.logger.Info("Limit guard periodic check started.", nil)

	items, err := lg.finder.GetDistinctFirstItems()
	if err != nil {
		lg.logger.Error("Limit Guard error.", logger.Context{"error": err})
	}

	lg.blacklist = make(map[string]bool, 10)

	for _, i := range items {
		tooOld := time.Now().Add(- tooOldDuration)
		if i.Created.Before(tooOld) {
			lg.blacklist[i.LimitKey] = true
			lg.sendNotificationLog(i.LimitKey)
		}
	}

	lg.logger.Info("Limit guard periodic check finished.", nil)
}

// TODO - format in order to be transformed to notification in logstash
func (lg *limitGuard) sendNotificationLog(key string) string {
	msg := "Smells like rotting message of key: "
	lg.logger.Warning(msg, nil)

	return msg
}
