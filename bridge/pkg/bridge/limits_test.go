package bridge

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/stretchr/testify/assert"
)

func resetLimits() {
	globalLimits.Store(nil)
	cachedLimits.Store(nil)
}

func TestIsOverLimit_NilChecker(t *testing.T) {
	resetLimits()
	assert.False(t, IsOverLimit(), "should return false when checker is nil")
}

func TestIsOverLimit_ResourceExceeded(t *testing.T) {
	resetLimits()
	lc := &limitsChecker{}
	lc.resourceExceeded.Store(true)
	globalLimits.Store(lc)

	assert.True(t, IsOverLimit(), "should return true when resource limit exceeded")
}

func TestIsOverLimit_MessageIntegrityExceeded(t *testing.T) {
	resetLimits()
	lc := &limitsChecker{}
	lc.messageIntegrityExceeded.Store(true)
	globalLimits.Store(lc)

	assert.True(t, IsOverLimit(), "should return true when message integrity limit exceeded")
}

func TestIsOverLimit_BothExceeded(t *testing.T) {
	resetLimits()
	lc := &limitsChecker{}
	lc.resourceExceeded.Store(true)
	lc.messageIntegrityExceeded.Store(true)
	globalLimits.Store(lc)

	assert.True(t, IsOverLimit(), "should return true when both limits exceeded")
}

func TestIsOverLimit_NoneExceeded(t *testing.T) {
	resetLimits()
	globalLimits.Store(&limitsChecker{})

	assert.False(t, IsOverLimit(), "should return false when no limits exceeded")
}

func TestIncrementDiscardCount_NilChecker(t *testing.T) {
	resetLimits()
	assert.NotPanics(t, func() { IncrementDiscardCount() }, "should not panic when checker is nil")
}

func TestIncrementDiscardCount_ResourceExceeded(t *testing.T) {
	resetLimits()
	lc := &limitsChecker{}
	lc.resourceExceeded.Store(true)
	globalLimits.Store(lc)

	IncrementDiscardCount()
	IncrementDiscardCount()
	IncrementDiscardCount()

	lc.mu.Lock()
	count := lc.resourceDiscardCount
	lc.mu.Unlock()

	assert.Equal(t, int64(3), count)
}

func TestIncrementDiscardCount_MessageIntegrityExceeded(t *testing.T) {
	resetLimits()
	lc := &limitsChecker{}
	lc.messageIntegrityExceeded.Store(true)
	globalLimits.Store(lc)

	IncrementDiscardCount()
	IncrementDiscardCount()

	lc.mu.Lock()
	count := lc.messageDiscardCount
	lc.mu.Unlock()

	assert.Equal(t, int64(2), count)
}

func TestStartLimitsChecker_DisabledWhenNoBackendUrl(t *testing.T) {
	resetLimits()
	origUrl := config.App.BackendUrl
	config.App.BackendUrl = ""
	defer func() { config.App.BackendUrl = origUrl }()

	done := make(chan struct{})
	go func() {
		StartLimitsChecker(context.TODO(), nil, events{publisher: testPublisher{}})
		close(done)
	}()

	<-done
	assert.Nil(t, globalLimits.Load(), "checker should not be initialized when BACKEND_URL is empty")
	assert.Nil(t, cachedLimits.Load(), "cached limits should be nil when BACKEND_URL is empty")
}

func TestFetchLimitsFromBackend_Success(t *testing.T) {
	resetLimits()

	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		assert.Equal(t, "/api/status", r.URL.Path)
		resp := map[string]interface{}{
			"status": "ok",
			"limits": map[string]interface{}{
				"messages":      2000,
				"storageGb":     5,
				"topologySlots": 10,
			},
		}
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(resp)
	}))
	defer server.Close()

	origUrl := config.App.BackendUrl
	config.App.BackendUrl = server.URL
	defer func() { config.App.BackendUrl = origUrl }()

	fetchLimitsFromBackend()

	limits := cachedLimits.Load()
	assert.NotNil(t, limits)
	assert.Equal(t, 5*1024, limits.storageLimitMB, "storageGb should be converted to MB via *1024")
	assert.Equal(t, 2000, limits.messageLimit)
}

func TestFetchLimitsFromBackend_RefetchUpdatesCachedValues(t *testing.T) {
	resetLimits()

	callCount := 0
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		callCount++
		var resp map[string]interface{}
		if callCount == 1 {
			resp = map[string]interface{}{
				"status": "ok",
				"limits": map[string]interface{}{
					"messages":  1000,
					"storageGb": 3,
				},
			}
		} else {
			resp = map[string]interface{}{
				"status": "ok",
				"limits": map[string]interface{}{
					"messages":  5000,
					"storageGb": 10,
				},
			}
		}
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(resp)
	}))
	defer server.Close()

	origUrl := config.App.BackendUrl
	config.App.BackendUrl = server.URL
	defer func() { config.App.BackendUrl = origUrl }()

	fetchLimitsFromBackend()
	limits := cachedLimits.Load()
	assert.NotNil(t, limits)
	assert.Equal(t, 3*1024, limits.storageLimitMB)
	assert.Equal(t, 1000, limits.messageLimit)

	fetchLimitsFromBackend()
	limits = cachedLimits.Load()
	assert.NotNil(t, limits)
	assert.Equal(t, 10*1024, limits.storageLimitMB, "should update to new storage value")
	assert.Equal(t, 5000, limits.messageLimit, "should update to new message value")
}

func TestFetchLimitsFromBackend_ErrorKeepsLastKnown(t *testing.T) {
	resetLimits()

	callCount := 0
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		callCount++
		if callCount == 1 {
			resp := map[string]interface{}{
				"status": "ok",
				"limits": map[string]interface{}{
					"messages":  2000,
					"storageGb": 5,
				},
			}
			w.Header().Set("Content-Type", "application/json")
			json.NewEncoder(w).Encode(resp)
		} else {
			w.WriteHeader(http.StatusInternalServerError)
		}
	}))
	defer server.Close()

	origUrl := config.App.BackendUrl
	config.App.BackendUrl = server.URL
	defer func() { config.App.BackendUrl = origUrl }()

	fetchLimitsFromBackend()
	limits := cachedLimits.Load()
	assert.NotNil(t, limits)
	assert.Equal(t, 5*1024, limits.storageLimitMB)
	assert.Equal(t, 2000, limits.messageLimit)

	fetchLimitsFromBackend()
	limits = cachedLimits.Load()
	assert.NotNil(t, limits, "cached limits should not be cleared on error")
	assert.Equal(t, 5*1024, limits.storageLimitMB, "should keep last-known storage value")
	assert.Equal(t, 2000, limits.messageLimit, "should keep last-known message value")
}

func TestFetchLimitsFromBackend_MissingLimitsKey(t *testing.T) {
	resetLimits()

	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		resp := map[string]interface{}{
			"status": "ok",
		}
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(resp)
	}))
	defer server.Close()

	origUrl := config.App.BackendUrl
	config.App.BackendUrl = server.URL
	defer func() { config.App.BackendUrl = origUrl }()

	fetchLimitsFromBackend()

	limits := cachedLimits.Load()
	assert.NotNil(t, limits, "cache should be set to zero values when limits key is missing")
	assert.Equal(t, 0, limits.storageLimitMB)
	assert.Equal(t, 0, limits.messageLimit)
}

func TestFetchLimitsFromBackend_ConnectionError(t *testing.T) {
	resetLimits()

	cachedLimits.Store(&backendLimits{storageLimitMB: 2048, messageLimit: 500})

	origUrl := config.App.BackendUrl
	config.App.BackendUrl = "http://127.0.0.1:1"
	defer func() { config.App.BackendUrl = origUrl }()

	fetchLimitsFromBackend()

	limits := cachedLimits.Load()
	assert.NotNil(t, limits, "cached limits should survive connection error")
	assert.Equal(t, 2048, limits.storageLimitMB)
	assert.Equal(t, 500, limits.messageLimit)
}

func TestCheckResourceLimit_ClearsExceededWhenLimitBecomesZero(t *testing.T) {
	resetLimits()
	lc := &limitsChecker{events: events{publisher: testPublisher{}}}
	lc.resourceExceeded.Store(true)
	lc.resourceDiscardCount = 42
	globalLimits.Store(lc)

	cachedLimits.Store(&backendLimits{storageLimitMB: 0, messageLimit: 1000})

	assert.True(t, IsOverLimit(), "should be over limit before check")

	lc.checkResourceLimit()

	assert.False(t, lc.resourceExceeded.Load(), "resourceExceeded should be cleared when storage limit is 0")
	assert.False(t, IsOverLimit(), "should no longer be over limit")
}

func TestCheckMessageLimit_ClearsExceededWhenLimitBecomesZero(t *testing.T) {
	resetLimits()
	lc := &limitsChecker{events: events{publisher: testPublisher{}}}
	lc.messageIntegrityExceeded.Store(true)
	lc.messageDiscardCount = 17
	globalLimits.Store(lc)

	cachedLimits.Store(&backendLimits{storageLimitMB: 5120, messageLimit: 0})

	assert.True(t, IsOverLimit(), "should be over limit before check")

	lc.checkMessageLimit()

	assert.False(t, lc.messageIntegrityExceeded.Load(), "messageIntegrityExceeded should be cleared when message limit is 0")
	assert.False(t, IsOverLimit(), "should no longer be over limit")
}

func TestFetchLimitsFromBackend_HadLimitsThenMissingClearsCache(t *testing.T) {
	resetLimits()

	callCount := 0
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		callCount++
		var resp map[string]interface{}
		if callCount == 1 {
			resp = map[string]interface{}{
				"status": "ok",
				"limits": map[string]interface{}{
					"messages":  2000,
					"storageGb": 5,
				},
			}
		} else {
			resp = map[string]interface{}{
				"status": "ok",
			}
		}
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(resp)
	}))
	defer server.Close()

	origUrl := config.App.BackendUrl
	config.App.BackendUrl = server.URL
	defer func() { config.App.BackendUrl = origUrl }()

	fetchLimitsFromBackend()
	limits := cachedLimits.Load()
	assert.NotNil(t, limits)
	assert.Equal(t, 5*1024, limits.storageLimitMB)
	assert.Equal(t, 2000, limits.messageLimit)

	fetchLimitsFromBackend()
	limits = cachedLimits.Load()
	assert.NotNil(t, limits, "cache should not be nil, should be zero-value")
	assert.Equal(t, 0, limits.storageLimitMB, "storage limit should be cleared when backend stops advertising limits")
	assert.Equal(t, 0, limits.messageLimit, "message limit should be cleared when backend stops advertising limits")
}
