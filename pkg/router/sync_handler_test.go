package router

import (
	"bytes"
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"starting-point/pkg/config"
	"starting-point/pkg/service"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"
)

// SyncRabbitMock starts the "process" and answers the reply-to callback with the given events
type SyncRabbitMock struct {
	service.RabbitSvc
	events []service.SyncEvent
}

func (r SyncRabbitMock) SendMessage(request *http.Request, _ storage.Topology, _ map[string]interface{}) (string, error) {
	replyId := request.Header.Get(utils.ReplyID)
	go func() {
		for _, event := range r.events {
			// The callback arrives through the HTTP route like from the bridge
			req := httptest.NewRequest(http.MethodPost, "/sync-reply/"+replyId, bytes.NewReader(event.Body))
			if event.Kind != service.SyncEvent_Result {
				req.Header.Set(pulseEventHeader, event.Kind)
			}
			Router(nil).ServeHTTP(httptest.NewRecorder(), req)
		}
	}()

	return "corr-1", nil
}

func runSync(t *testing.T, events ...service.SyncEvent) *httptest.ResponseRecorder {
	t.Helper()
	service.Cache = &CacheMock{}
	service.RabbitMq = SyncRabbitMock{events: events}
	config.Sync.Timeout = 1

	r := httptest.NewRequest(http.MethodPost, "/topologies/Topology/nodes/Node/token/Token/run-sync", bytes.NewReader([]byte(`{"a":1}`)))
	w := httptest.NewRecorder()
	Router(nil).ServeHTTP(w, r)

	return w
}

func TestRunSync_Result(t *testing.T) {
	w := runSync(t, service.SyncEvent{Kind: service.SyncEvent_Result, Body: []byte(`{"stock":5}`)})

	assert.Equal(t, http.StatusOK, w.Code)
	assert.JSONEq(t, `{"stock":5}`, w.Body.String())
	assert.Equal(t, "corr-1", w.Header().Get(utils.CorrelationID))
}

func TestRunSync_Error(t *testing.T) {
	w := runSync(t, service.SyncEvent{Kind: service.SyncEvent_Error, Body: []byte(`{"message":"boom","node_name":"erp","trash_id":"t1"}`)})

	require.Equal(t, http.StatusUnprocessableEntity, w.Code)
	body := map[string]interface{}{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &body))
	assert.Equal(t, "boom", body["error"])
	assert.Equal(t, "erp", body["node"])
	assert.Equal(t, "t1", body["trash_id"])
	assert.Equal(t, "corr-1", body["correlation_id"])
}

func TestRunSync_Stopped(t *testing.T) {
	w := runSync(t, service.SyncEvent{Kind: service.SyncEvent_Stopped, Body: []byte(`{}`)})

	assert.Equal(t, http.StatusNoContent, w.Code)
}

func TestRunSync_UserTaskEndsWaitAtOnce(t *testing.T) {
	start := time.Now()
	w := runSync(t, service.SyncEvent{Kind: service.SyncEvent_Pending, Body: []byte(`{"reason":"user_task"}`)})

	assert.Equal(t, http.StatusAccepted, w.Code)
	assert.Contains(t, w.Body.String(), `"state":"pending"`)
	assert.Less(t, time.Since(start), 900*time.Millisecond)
}

func TestRunSync_RepeatKeepsWaitingForResult(t *testing.T) {
	w := runSync(t,
		service.SyncEvent{Kind: service.SyncEvent_Pending, Body: []byte(`{"reason":"repeat"}`)},
		service.SyncEvent{Kind: service.SyncEvent_Result, Body: []byte(`{"ok":true}`)},
	)

	assert.Equal(t, http.StatusOK, w.Code)
	assert.JSONEq(t, `{"ok":true}`, w.Body.String())
}

func TestRunSync_TimeoutAfterRepeat(t *testing.T) {
	w := runSync(t, service.SyncEvent{Kind: service.SyncEvent_Pending, Body: []byte(`{"reason":"repeat"}`)})

	assert.Equal(t, http.StatusAccepted, w.Code)
	assert.Contains(t, w.Body.String(), `"state":"running"`)
	assert.Contains(t, w.Body.String(), `"reason":"repeat"`)
}

func TestSyncReply_UnknownReplyId(t *testing.T) {
	w := httptest.NewRecorder()
	Router(nil).ServeHTTP(w, httptest.NewRequest(http.MethodPost, "/sync-reply/unknown", bytes.NewReader([]byte(`{}`))))

	assert.Equal(t, http.StatusOK, w.Code)
	assert.JSONEq(t, `{"delivered":false}`, w.Body.String())
}

func TestRunSync_WaitLimitRefusesBeforeStarting(t *testing.T) {
	config.Sync.WaitLimit = 1
	syncWaiting.Add(1)
	t.Cleanup(func() {
		syncWaiting.Add(-1)
		config.Sync.WaitLimit = 200
	})

	w := runSync(t, service.SyncEvent{Kind: service.SyncEvent_Result, Body: []byte(`{}`)})

	assert.Equal(t, http.StatusTooManyRequests, w.Code)
	assert.Empty(t, w.Header().Get(utils.CorrelationID), "the run must not start")
}

func TestWaitForResult_CallerGoneEndsWait(t *testing.T) {
	ctx, cancel := context.WithCancel(context.Background())
	cancel()

	start := time.Now()
	result := waitForResult(ctx, make(chan service.SyncEvent), time.Minute)

	assert.Nil(t, result.Event)
	assert.Less(t, time.Since(start), time.Second)
}
