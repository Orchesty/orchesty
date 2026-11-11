package worker

import (
	"bytes"
	"context"
	"encoding/json"
	"net/http"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
)

const replyCallbackTimeout = 15 * time.Second

// Reply event kinds sent to a synchronous caller (reply-to) in addition to the response
// node's result and the Pulse confirm / question events.
const (
	// ReplyEvent_Error - a message on the main branch ended in trash (or was discarded)
	ReplyEvent_Error = "error"
	// ReplyEvent_Pending - a message on the main branch was parked (repeat, limit, user task)
	ReplyEvent_Pending = "pending"
	// ReplyEvent_Stopped - a message left the main branch without reaching a response node
	ReplyEvent_Stopped = "stopped"
)

// Pending reasons carried by ReplyEvent_Pending
const (
	ReplyReason_Repeat   = "repeat"
	ReplyReason_Limit    = "limit"
	ReplyReason_UserTask = "user_task"
)

// ReplyEvent is the body of error / pending / stopped callbacks
type ReplyEvent struct {
	Kind          string `json:"kind"`
	CorrelationId string `json:"correlation_id"`
	TopologyName  string `json:"topology_name,omitempty"`
	ProcessId     string `json:"process_id,omitempty"`
	NodeId        string `json:"node_id"`
	NodeName      string `json:"node_name"`
	Message       string `json:"message,omitempty"`
	Reason        string `json:"reason,omitempty"`
	TrashId       string `json:"trash_id,omitempty"`
}

// NewReplyEvent fills the event from the message headers
func NewReplyEvent(dto *model.ProcessMessage, kind string) ReplyEvent {
	return ReplyEvent{
		Kind:          kind,
		CorrelationId: dto.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
		ProcessId:     dto.GetHeaderOrDefault(enum.Header_ProcessId, ""),
		NodeId:        dto.GetHeaderOrDefault(enum.Header_NodeId, ""),
		NodeName:      dto.GetHeaderOrDefault(enum.Header_NodeName, ""),
	}
}

// PostReplyEvent POSTs a JSON event to the run's reply-to callback (best effort).
// The kind is sent in the `pulse-event` header, the request id in `reply-id`.
func PostReplyEvent(client http.Client, dto *model.ProcessMessage, kind string, event any) {
	replyTo := dto.GetHeaderOrDefault(enum.Header_ReplyTo, "")
	if replyTo == "" {
		return
	}

	body, err := json.Marshal(event)
	if err != nil {
		log.Error().EmbedObject(dto).Err(err).Msgf("%s: failed to encode callback", kind)
		return
	}

	ctx, cancel := context.WithTimeout(context.Background(), replyCallbackTimeout)
	defer cancel()

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, replyTo, bytes.NewReader(body))
	if err != nil {
		log.Error().EmbedObject(dto).Err(err).Msgf("%s: failed to build callback request", kind)
		return
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set(enum.Header_PulseEvent, kind)
	if replyID := dto.GetHeaderOrDefault(enum.Header_ReplyId, ""); replyID != "" {
		req.Header.Set(enum.Header_ReplyId, replyID)
	}

	resp, err := client.Do(req)
	if err != nil {
		log.Error().EmbedObject(dto).Err(err).Msgf("%s: callback POST to %s failed", kind, replyTo)
		return
	}
	defer resp.Body.Close()
}
