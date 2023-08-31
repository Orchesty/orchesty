package limiter

import (
	"fmt"
	"github.com/hanaboso/go-utils/pkg/intx"
	"github.com/rs/zerolog/log"
	"limiter/pkg/model"
	"sync"
	"time"
)

type LimitSvc struct {
	limits map[string]*model.Limit
	lock   *sync.Mutex
}

func (this *LimitSvc) FillLimits(limits map[string]int) {
	for key := range limits {
		this.UpsertLimits(model.ParseLimits(key))
	}
}

// TODO temporary method to check limiter deadlock problem
func (this *LimitSvc) ReFillLimits(limits map[string]int) {
	this.lock.Lock()
	defer this.lock.Unlock()

	usedToHave := len(this.limits)
	this.limits = make(map[string]*model.Limit)
	this.limits[""] = &model.Limit{
		FullKey:       "",
		SystemKey:     "",
		UserKey:       "",
		Time:          1,
		TimeToRefresh: 1,
		Maximum:       999,
		Allowed:       999,
	}

	for key := range limits {
		limits := model.ParseLimits(key)

		for _, limit := range limits {
			if current, ok := this.limits[limit.FullKey]; ok {
				current.Time = limit.Time
				current.Maximum = limit.Maximum
			} else {
				this.limits[limit.FullKey] = &limit
			}
		}
	}

	refreshedCount := len(this.limits)
	if usedToHave < refreshedCount {
		log.Error().Err(fmt.Errorf("service limit count did not match, had: %d, refreshed to: %d", usedToHave, refreshedCount)).Send()
	}
}

func (this *LimitSvc) UpsertLimits(limits []model.Limit) {
	this.lock.Lock()
	defer this.lock.Unlock()

	for _, limit := range limits {
		if current, ok := this.limits[limit.FullKey]; ok {
			current.Time = limit.Time
			current.Maximum = limit.Maximum
		} else {
			this.limits[limit.FullKey] = &limit
		}
	}
}

func (this *LimitSvc) FinishProcess(keys []string) {
	this.lock.Lock()
	defer this.lock.Unlock()

	for _, key := range keys {
		if limit, ok := this.limits[key]; ok {
			limit.FinishProcess()
		}
	}
}

func (this *LimitSvc) AllowedMessages(keys []string) int {
	this.lock.Lock()
	defer this.lock.Unlock()

	allowed := 0
	for i, key := range keys {
		if limit, ok := this.limits[key]; ok {
			if i == 0 {
				allowed = limit.AllowedBatch()
			} else {
				allowed = intx.Min(limit.AllowedBatch(), limit.Allowed)
			}
		} else {
			log.Fatal().Err(fmt.Errorf("limit [%s] missing", key)).Send()
		}
	}

	if allowed > 0 {
		for _, key := range keys {
			limit := this.limits[key]
			limit.Allowed = intx.Max(limit.Allowed-allowed, 0)
			limit.Running += allowed
		}
	}

	return allowed
}

func (this *LimitSvc) RefreshMissingMessages(keys []string, amount int) {
	this.lock.Lock()
	defer this.lock.Unlock()

	for _, key := range keys {
		limit := this.limits[key]
		limit.Allowed += amount
		limit.Running = intx.Max(0, limit.Running-amount)
	}
}

func (this *LimitSvc) startRefreshTicker() {
	for range time.Tick(time.Second) {
		this.lock.Lock()
		for key, limit := range this.limits {
			if remove := limit.Refresh(); remove {
				if key != "" {
					delete(this.limits, key)
				}
			}
		}
		this.lock.Unlock()
	}
}

func NewService() *LimitSvc {
	svc := &LimitSvc{
		limits: make(map[string]*model.Limit),
		lock:   &sync.Mutex{},
	}
	svc.limits[""] = &model.Limit{
		FullKey:       "",
		SystemKey:     "",
		UserKey:       "",
		Time:          1,
		TimeToRefresh: 1,
		Maximum:       999,
		Allowed:       999,
	}

	go svc.startRefreshTicker()

	return svc
}
