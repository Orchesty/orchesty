package bridge

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
	"sync"
	"sync/atomic"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"github.com/rs/zerolog/log"
)

var limitsCheckInterval = time.Duration(config.App.LimitsCheckInterval) * time.Second

type backendLimits struct {
	storageLimitMB int
	messageLimit   int
}

var cachedLimits atomic.Pointer[backendLimits]

type limitsChecker struct {
	mongodb                  *mongo.MongoDb
	metricsReader            *mongo.MetricsReader
	events                   events
	mu                       sync.Mutex
	resourceExceeded         atomic.Bool
	messageIntegrityExceeded atomic.Bool
	resourceDiscardCount     int64
	messageDiscardCount      int64
}

var globalLimits atomic.Pointer[limitsChecker]

func IsOverLimit() bool {
	lc := globalLimits.Load()
	if lc == nil {
		return false
	}
	return lc.resourceExceeded.Load() || lc.messageIntegrityExceeded.Load()
}

func IncrementDiscardCount() {
	lc := globalLimits.Load()
	if lc == nil {
		return
	}
	lc.mu.Lock()
	defer lc.mu.Unlock()

	if lc.resourceExceeded.Load() {
		lc.resourceDiscardCount++
	}
	if lc.messageIntegrityExceeded.Load() {
		lc.messageDiscardCount++
	}
}

type statusResponse struct {
	Limits *statusLimits `json:"limits"`
}

type statusLimits struct {
	Messages  int `json:"messages"`
	StorageGb int `json:"storageGb"`
}

var limitsHTTPClient = &http.Client{Timeout: 10 * time.Second}

func fetchLimitsFromBackend() {
	url := fmt.Sprintf("%s/api/status", strings.TrimRight(config.App.BackendUrl, "/"))

	resp, err := limitsHTTPClient.Get(url)
	if err != nil {
		log.Warn().Err(err).Str("url", url).Msg("failed to fetch limits from backend")
		return
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		log.Warn().Int("status_code", resp.StatusCode).Str("url", url).Msg("backend returned non-200 for limits fetch")
		return
	}

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		log.Warn().Err(err).Msg("failed to read backend status response body")
		return
	}

	var status statusResponse
	if err := json.Unmarshal(body, &status); err != nil {
		log.Warn().Err(err).Msg("failed to parse backend status response")
		return
	}

	if status.Limits == nil {
		log.Warn().Str("url", url).Msg("backend status response has no limits key (non-cloud mode?), clearing limits")
		cachedLimits.Store(&backendLimits{storageLimitMB: 0, messageLimit: 0})
		return
	}

	limits := &backendLimits{
		storageLimitMB: status.Limits.StorageGb * 1024,
		messageLimit:   status.Limits.Messages,
	}
	cachedLimits.Store(limits)

	log.Info().
		Int("storage_gb", status.Limits.StorageGb).
		Int("storage_limit_mb", limits.storageLimitMB).
		Int("message_limit", limits.messageLimit).
		Msg("fetched limits from backend")
}

func StartLimitsChecker(ctx context.Context, mongodb *mongo.MongoDb, ev events) {
	if config.App.BackendUrl == "" {
		log.Info().Msg("BACKEND_URL not set, limits checker disabled")
		return
	}

	metricsReader := mongo.NewMetricsReader()

	lc := &limitsChecker{
		mongodb:       mongodb,
		metricsReader: metricsReader,
		events:        ev,
	}
	globalLimits.Store(lc)

	log.Info().
		Str("backend_url", config.App.BackendUrl).
		Msg("starting global limits checker")

	ticker := time.NewTicker(limitsCheckInterval)
	defer ticker.Stop()
	defer metricsReader.Close()

	fetchLimitsFromBackend()
	lc.check()

	for {
		select {
		case <-ctx.Done():
			return
		case <-ticker.C:
			fetchLimitsFromBackend()
			lc.check()
		}
	}
}

func (lc *limitsChecker) check() {
	lc.checkResourceLimit()
	lc.checkMessageLimit()
}

func (lc *limitsChecker) checkResourceLimit() {
	limits := cachedLimits.Load()
	if limits == nil || limits.storageLimitMB <= 0 {
		if lc.resourceExceeded.CompareAndSwap(true, false) {
			lc.mu.Lock()
			discarded := lc.resourceDiscardCount
			lc.resourceDiscardCount = 0
			lc.mu.Unlock()
			log.Info().Int64("discarded_total", discarded).Msg("storage limit disabled, clearing exceeded state")
			lc.sendRecoveryNotifications("storage", 0, 0, discarded)
		}
		return
	}
	limit := limits.storageLimitMB

	storageMB, err := lc.metricsReader.GetLatestStorageMetric()
	if err != nil {
		log.Warn().Err(err).Msg("failed to read storage metric, skipping check cycle")
		return
	}

	rabbitMB, err := lc.metricsReader.GetLatestRabbitMetric()
	if err != nil {
		log.Warn().Err(err).Msg("failed to read rabbitmq metric, skipping check cycle")
		return
	}

	lokiMB, err := lc.metricsReader.GetLatestLokiMetric()
	if err != nil {
		log.Warn().Err(err).Msg("failed to read loki metric, skipping check cycle")
		return
	}

	totalMB := storageMB + rabbitMB + lokiMB
	exceeded := totalMB > float64(limit)
	wasExceeded := lc.resourceExceeded.Load()

	if exceeded && !wasExceeded {
		lc.resourceExceeded.Store(true)
		lc.mu.Lock()
		lc.resourceDiscardCount = 0
		lc.mu.Unlock()

		log.Warn().
			Float64("total_mb", totalMB).
			Int("storage_limit", limit).
			Msg("storage limit exceeded, discarding all incoming messages")

		lc.sendLimitNotifications("storage", totalMB, float64(limit), 0)
	} else if !exceeded && wasExceeded {
		lc.resourceExceeded.Store(false)
		lc.mu.Lock()
		discarded := lc.resourceDiscardCount
		lc.resourceDiscardCount = 0
		lc.mu.Unlock()

		log.Warn().
			Float64("total_mb", totalMB).
			Int("storage_limit", limit).
			Int64("discarded_total", discarded).
			Msg("storage limit recovered")

		lc.sendRecoveryNotifications("storage", totalMB, float64(limit), discarded)
	} else {
		lc.mu.Lock()
		discarded := lc.resourceDiscardCount
		lc.mu.Unlock()

		log.Trace().
			Float64("total_mb", totalMB).
			Int("storage_limit", limit).
			Bool("exceeded", exceeded).
			Int64("discarded", discarded).
			Msg("storage limit check")
	}
}

func (lc *limitsChecker) checkMessageLimit() {
	limits := cachedLimits.Load()
	if limits == nil || limits.messageLimit <= 0 {
		if lc.messageIntegrityExceeded.CompareAndSwap(true, false) {
			lc.mu.Lock()
			discarded := lc.messageDiscardCount
			lc.messageDiscardCount = 0
			lc.mu.Unlock()
			log.Info().Int64("discarded_total", discarded).Msg("message limit disabled, clearing exceeded state")
			lc.sendRecoveryNotifications("message", 0, 0, discarded)
		}
		return
	}
	limit := limits.messageLimit

	limiterCount, err := lc.mongodb.CountLimiterMessages()
	if err != nil {
		log.Warn().Err(err).Msg("failed to count limiter messages, skipping check cycle")
		return
	}

	trashCount, err := lc.mongodb.CountTrashMessages()
	if err != nil {
		log.Warn().Err(err).Msg("failed to count trash messages, skipping check cycle")
		return
	}

	totalCount := limiterCount + trashCount
	exceeded := totalCount > int64(limit)
	wasExceeded := lc.messageIntegrityExceeded.Load()

	if exceeded && !wasExceeded {
		lc.messageIntegrityExceeded.Store(true)
		lc.mu.Lock()
		lc.messageDiscardCount = 0
		lc.mu.Unlock()

		log.Warn().
			Int64("total_count", totalCount).
			Int("limit", limit).
			Msg("message limit exceeded, discarding all incoming messages")

		lc.sendLimitNotifications("message", float64(totalCount), float64(limit), 0)
	} else if !exceeded && wasExceeded {
		lc.messageIntegrityExceeded.Store(false)
		lc.mu.Lock()
		discarded := lc.messageDiscardCount
		lc.messageDiscardCount = 0
		lc.mu.Unlock()

		log.Warn().
			Int64("total_count", totalCount).
			Int("limit", limit).
			Int64("discarded_total", discarded).
			Msg("message limit recovered")

		lc.sendRecoveryNotifications("message", float64(totalCount), float64(limit), discarded)
	} else {
		lc.mu.Lock()
		discarded := lc.messageDiscardCount
		lc.mu.Unlock()

		log.Trace().
			Int64("total_count", totalCount).
			Int("limit", limit).
			Bool("exceeded", exceeded).
			Int64("discarded", discarded).
			Msg("message limit check")
	}
}

func (lc *limitsChecker) sendLimitNotifications(limitType string, currentValue, limitValue float64, discardedCount int64) {
	message := fmt.Sprintf("%s limit exceeded: %.0f / %.0f", limitType, currentValue, limitValue)
	lc.events.sendLimitOverflowEvent(limitType, currentValue, limitValue, discardedCount, message)
	sendLimitOverflowStatus(limitType, currentValue, limitValue, discardedCount, message)
}

func (lc *limitsChecker) sendRecoveryNotifications(limitType string, currentValue, limitValue float64, discardedCount int64) {
	message := fmt.Sprintf("%s limit recovered: %.0f / %.0f (discarded %d messages)", limitType, currentValue, limitValue, discardedCount)
	lc.events.sendLimitRecoveredEvent(limitType, currentValue, limitValue, discardedCount, message)
	sendLimitRecoveredStatus(limitType, currentValue, limitValue, discardedCount, message)
}
