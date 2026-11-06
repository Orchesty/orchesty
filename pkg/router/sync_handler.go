package router

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
	"sync/atomic"
	"time"

	"github.com/google/uuid"
	"github.com/gorilla/mux"
	"starting-point/pkg/config"
	"starting-point/pkg/service"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"
)

// pulseEventHeader carries the kind of a reply callback (result when missing)
const pulseEventHeader = "pulse-event"

// Pending reasons the caller should not wait for (a human decides)
var humanPendingReasons = map[string]bool{"user_task": true, "confirm": true, "question": true}

// HandleRunSyncByID runs topology by ID and waits for its result
func HandleRunSyncByID(w http.ResponseWriter, r *http.Request) {
	handleByID(w, r, true)
}

// HandleRunSyncByName runs topology by name and waits for its result
func HandleRunSyncByName(w http.ResponseWriter, r *http.Request) {
	handleByName(w, r, true)
}

// HandleRunSyncByApplication runs topology by application (webhook) and waits for its result
func HandleRunSyncByApplication(w http.ResponseWriter, r *http.Request) {
	handleByApplication(w, r, true)
}

// HandleSyncReply receives reply-to callbacks of the bridge for a waiting run-sync request
func HandleSyncReply(w http.ResponseWriter, r *http.Request) {
	replyId := mux.Vars(r)["replyId"]
	body, err := io.ReadAll(r.Body)
	if err != nil {
		writeErrorResponse(w, http.StatusBadRequest, "Cannot read body!")
		return
	}

	kind := r.Header.Get(pulseEventHeader)
	if kind == "" {
		kind = service.SyncEvent_Result
	}

	delivered := service.Waiter.Resolve(replyId, service.SyncEvent{Kind: kind, Body: body})
	writeResponse(w, map[string]interface{}{"delivered": delivered})
}

var syncWaiting atomic.Int64

func acquireSyncWait() bool {
	limit := int64(config.Sync.WaitLimit)
	if syncWaiting.Add(1) > limit && limit > 0 {
		syncWaiting.Add(-1)

		return false
	}

	return true
}

func processMessageSync(w http.ResponseWriter, r *http.Request, topology *storage.Topology, init map[string]interface{}) {
	// Checked before the run starts, so a refused caller can simply retry
	if !acquireSyncWait() {
		w.Header().Set("Retry-After", "1")
		writeErrorResponse(w, http.StatusTooManyRequests, "Too many synchronous runs are waiting, please try again later!")
		return
	}
	defer syncWaiting.Add(-1)

	replyId := uuid.New().String()
	events := service.Waiter.Register(replyId)
	defer service.Waiter.Cancel(replyId)

	r.Header.Set(utils.ReplyTo, fmt.Sprintf("%s/sync-reply/%s", strings.TrimRight(config.Sync.ReplyBaseUrl, "/"), replyId))
	r.Header.Set(utils.ReplyID, replyId)

	corrId, _ := service.RabbitMq.SendMessage(r, *topology, init)
	if corrId == "" {
		writeErrorResponse(w, http.StatusInternalServerError, "Error while processing message, please try again later!")
		return
	}

	result := waitForResult(r.Context(), events, time.Duration(config.Sync.Timeout)*time.Second)
	if r.Context().Err() != nil {
		// The caller hung up; the run goes on, nobody reads this answer
		return
	}
	writeSyncResult(w, corrId, result)
}

// syncResult is the outcome of waiting for a run-sync request
type syncResult struct {
	// Event - the terminal event, nil on timeout
	Event *service.SyncEvent
	// PendingReason - last reason the run was parked for (repeat, limit, ...)
	PendingReason string
}

// waitForResult waits for the first terminal event of the main branch. A run parked on a human
// (user task, confirm, question) ends the wait at once; a repeat or limit only records the reason
// and keeps waiting, the result may still come before the timeout. A caller that hangs up ends the
// wait too, so its slot is free at once.
func waitForResult(ctx context.Context, events <-chan service.SyncEvent, timeout time.Duration) syncResult {
	timer := time.NewTimer(timeout)
	defer timer.Stop()

	result := syncResult{}
	for {
		select {
		case <-ctx.Done():
			return result
		case <-timer.C:
			return result
		case event := <-events:
			switch event.Kind {
			case service.SyncEvent_Pending:
				reason := pendingReason(event.Body)
				result.PendingReason = reason
				if humanPendingReasons[reason] {
					result.Event = &event

					return result
				}
			case service.SyncEvent_Confirm, service.SyncEvent_Question:
				result.PendingReason = event.Kind
				result.Event = &service.SyncEvent{Kind: service.SyncEvent_Pending, Body: event.Body}

				return result
			default:
				result.Event = &event

				return result
			}
		}
	}
}

func pendingReason(body []byte) string {
	event := struct {
		Reason string `json:"reason"`
	}{}
	_ = json.Unmarshal(body, &event)

	return event.Reason
}

func writeSyncResult(w http.ResponseWriter, corrId string, result syncResult) {
	w.Header().Set(utils.CorrelationID, corrId)

	if result.Event == nil {
		w.Header().Set("Content-Type", "application/json; charset=utf-8")
		w.WriteHeader(http.StatusAccepted)
		writeResponse(w, map[string]interface{}{"state": "running", "correlation_id": corrId, "reason": result.PendingReason})

		return
	}

	switch result.Event.Kind {
	case service.SyncEvent_Result:
		if json.Valid(result.Event.Body) {
			w.Header().Set("Content-Type", "application/json; charset=utf-8")
		}
		_, _ = w.Write(result.Event.Body)
	case service.SyncEvent_Error:
		event := map[string]interface{}{}
		_ = json.Unmarshal(result.Event.Body, &event)
		w.Header().Set("Content-Type", "application/json; charset=utf-8")
		w.WriteHeader(http.StatusUnprocessableEntity)
		writeResponse(w, map[string]interface{}{
			"state":          "error",
			"correlation_id": corrId,
			"error":          event["message"],
			"node":           event["node_name"],
			"trash_id":       event["trash_id"],
			"reason":         event["reason"],
		})
	case service.SyncEvent_Pending:
		w.Header().Set("Content-Type", "application/json; charset=utf-8")
		w.WriteHeader(http.StatusAccepted)
		writeResponse(w, map[string]interface{}{"state": "pending", "correlation_id": corrId, "reason": result.PendingReason})
	case service.SyncEvent_Stopped:
		w.WriteHeader(http.StatusNoContent)
	default:
		w.Header().Set("Content-Type", "application/json; charset=utf-8")
		w.WriteHeader(http.StatusAccepted)
		writeResponse(w, map[string]interface{}{"state": "running", "correlation_id": corrId})
	}
}
