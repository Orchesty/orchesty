package limiter

import "time"

type Guard interface {
	StartPeriodicCheck(duration time.Duration)
}

type limitsGuard struct {

}
