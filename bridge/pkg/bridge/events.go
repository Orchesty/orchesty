package bridge

import (
	"encoding/json"
	"fmt"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/rabbit"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/rs/zerolog/log"
)

type eventEnvelope struct {
	EventID    string         `json:"event_id"`
	EventType  string         `json:"event_type"`
	OccurredAt string         `json:"occurred_at"`
	TenantID   string         `json:"tenant_id"`
	Topology   *topologyRef   `json:"topology,omitempty"`
	Node       *nodeRef       `json:"node,omitempty"`
	Severity   string         `json:"severity"`
	Context    map[string]any `json:"context,omitempty"`
	Message    string         `json:"message,omitempty"`
}

type topologyRef struct {
	ID   string `json:"id"`
	Name string `json:"name"`
}

type nodeRef struct {
	ID   string `json:"id"`
	Name string `json:"name"`
}

type events struct {
	publisher types.Publisher
}

func newEvents(rabbitContainer rabbit.Container) events {
	return events{publisher: rabbitContainer.Events}
}

func (ev events) sendLimitOverflowEvent(limitType string, currentValue, limitValue float64, discardedCount int64, message string) {
	envelope := eventEnvelope{
		EventID:    fmt.Sprintf("limit-%s-%d", limitType, time.Now().UnixMilli()),
		EventType:  "limit_overflow",
		OccurredAt: time.Now().UTC().Format(time.RFC3339),
		TenantID:   "orchesty",
		Severity:   "critical",
		Context: map[string]any{
			"limit_type":      limitType,
			"current_value":   currentValue,
			"limit_value":     limitValue,
			"discarded_count": discardedCount,
		},
		Message: message,
	}

	body, err := json.Marshal(envelope)
	if err != nil {
		log.Err(err).Msg("failed to marshal limit overflow event envelope")
		return
	}

	if err := ev.publisher.Publish(amqp.Publishing{
		ContentType: "application/json",
		Body:        body,
	}); err != nil {
		log.Err(err).Msg("failed to publish limit overflow event to orchesty.events")
	}
}

func (ev events) sendLimitRecoveredEvent(limitType string, currentValue, limitValue float64, discardedCount int64, message string) {
	envelope := eventEnvelope{
		EventID:    fmt.Sprintf("limit-%s-%d", limitType, time.Now().UnixMilli()),
		EventType:  "limit_recovered",
		OccurredAt: time.Now().UTC().Format(time.RFC3339),
		TenantID:   "orchesty",
		Severity:   "info",
		Context: map[string]any{
			"limit_type":      limitType,
			"current_value":   currentValue,
			"limit_value":     limitValue,
			"discarded_count": discardedCount,
		},
		Message: message,
	}

	body, err := json.Marshal(envelope)
	if err != nil {
		log.Err(err).Msg("failed to marshal limit recovered event envelope")
		return
	}

	if err := ev.publisher.Publish(amqp.Publishing{
		ContentType: "application/json",
		Body:        body,
	}); err != nil {
		log.Err(err).Msg("failed to publish limit recovered event to orchesty.events")
	}
}

func (ev events) send(msg *model.ProcessMessage, trashID, topologyName string) {
	envelope := eventEnvelope{
		EventID:    fmt.Sprintf("trash-%s-%d", msg.GetHeaderOrDefault(enum.Header_CorrelationId, ""), time.Now().UnixMilli()),
		EventType:  "topology_failed_message",
		OccurredAt: time.Now().UTC().Format(time.RFC3339),
		TenantID:   "orchesty",
		Severity:   "warning",
		Topology: &topologyRef{
			ID:   msg.GetHeaderOrDefault(enum.Header_TopologyId, ""),
			Name: topologyName,
		},
		Node: &nodeRef{
			ID:   msg.GetHeaderOrDefault(enum.Header_NodeId, ""),
			Name: msg.GetHeaderOrDefault(enum.Header_NodeName, ""),
		},
		Context: map[string]any{
			"trash_id":       trashID,
			"correlation_id": msg.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
			"process_id":     msg.GetHeaderOrDefault(enum.Header_ProcessId, ""),
			"result_message": msg.GetHeaderOrDefault(enum.Header_ResultMessage, "Message thrown into trash"),
		},
		Message: msg.GetHeaderOrDefault(enum.Header_ResultMessage, "Message thrown into trash"),
	}

	body, err := json.Marshal(envelope)
	if err != nil {
		log.Err(err).Msg("failed to marshal trash event envelope")
		return
	}

	if err := ev.publisher.Publish(amqp.Publishing{
		ContentType: "application/json",
		Body:        body,
	}); err != nil {
		log.Err(err).Msg("failed to publish trash event to orchesty.events")
	}
}
