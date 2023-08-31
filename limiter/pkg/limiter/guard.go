package limiter

import (
	"limiter/pkg/logger"
	"limiter/pkg/notification"
	"limiter/pkg/storage"

	"fmt"
	"time"
)

// Guard should be used for checking the limits and for deciding if and item is blacklisted
type Guard interface {
	IsOnBlacklist(key string) bool
	Check(tooOldDuration time.Duration)
}

// LimitGuard is the implementation of Guard interface
type LimitGuard struct {
	blacklist map[string]bool
	finder    storage.DistinctFinder
	logger    logger.Logger
}

// NewLimitGuard returns new instance of Guard
func NewLimitGuard(finder storage.DistinctFinder, logger logger.Logger) *LimitGuard {
	return &LimitGuard{make(map[string]bool, 10), finder, logger}
}

// IsOnBlacklist returns whether the key is currently blacklisted or not
func (lg *LimitGuard) IsOnBlacklist(key string) bool {
	_, ok := lg.blacklist[key]

	return ok
}

// Check updates the internal blacklist by the keys, that hold messages more then for tooOldDuration
func (lg *LimitGuard) Check(tooOldDuration time.Duration) {
	lg.logger.Info("Limit guard periodic check started.", nil)

	items, err := lg.finder.GetDistinctFirstItems()
	if err != nil {
		lg.logger.Error("Limit Guard error.", logger.Context{"error": err})
	}

	lg.blacklist = make(map[string]bool, 10)

	for _, i := range items {
		tooOld := time.Now().Add(-tooOldDuration)
		if i.Created.Before(tooOld) {
			// add limit key to blacklist
			lg.blacklist[i.LimitKey] = true
			lg.sendNotificationLog(i)
		}
	}

	lg.logger.Info("Limit guard periodic check finished.", nil)
}

// sendNotificationLog logs message with notification type context which is handles specially in logstash
func (lg *LimitGuard) sendNotificationLog(message *storage.Message) {
	lg.logger.Info(
		fmt.Sprintf("There is a limiter message rotting in storage for a long time, key: '%s'", message.LimitKey),
		logger.Context{
			"type":              "notification",
			"notification_type": notification.LimitExceeded,
		},
	)
}
