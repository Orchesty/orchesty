package counter

import (
	"encoding/json"
	"fmt"
	"time"

	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/model"
	amqp "github.com/rabbitmq/amqp091-go"
)

const longRunThreshold = 5 * time.Minute

type eventEnvelope struct {
	EventID    string         `json:"event_id"`
	EventType  string         `json:"event_type"`
	OccurredAt string         `json:"occurred_at"`
	TenantID   string         `json:"tenant_id"`
	Topology   *topologyRef   `json:"topology,omitempty"`
	Run        *runRef        `json:"run,omitempty"`
	Severity   string         `json:"severity"`
	Context    map[string]any `json:"context,omitempty"`
	Message    string         `json:"message,omitempty"`
}

type topologyRef struct {
	ID   string `json:"id"`
	Name string `json:"name"`
}

type runRef struct {
	ID         string `json:"id"`
	DurationMs int64  `json:"duration_ms"`
}

func sendEvents(publisher *rabbitmq.Publisher, process model.Process, topology model.Topology) {
	finished := time.Now()
	if process.Finished != nil {
		finished = *process.Finished
	}
	durationMs := finished.Sub(process.Created).Milliseconds()

	topo := &topologyRef{
		ID:   process.TopologyId,
		Name: topology.Name,
	}
	run := &runRef{
		ID:         process.Id,
		DurationMs: durationMs,
	}

	if process.Nok > 0 {
		publishEvent(publisher, "topology_failed", "topology.failed", "error", topo, run,
			map[string]any{
				"correlation_id": process.Id,
				"ok_count":       process.Ok,
				"nok_count":      process.Nok,
			},
			fmt.Sprintf("Topology %s failed with %d errors", topology.Name, process.Nok),
		)
	}

	if durationMs > longRunThreshold.Milliseconds() {
		publishEvent(publisher, "topology_slow", "topology.slow", "warning", topo, run,
			map[string]any{
				"correlation_id": process.Id,
				"duration_sec":   durationMs / 1000,
			},
			fmt.Sprintf("Topology %s took %ds", topology.Name, durationMs/1000),
		)
	}
}

func publishEvent(publisher *rabbitmq.Publisher, eventType, routingKey, severity string, topo *topologyRef, run *runRef, ctx map[string]any, message string) {
	envelope := eventEnvelope{
		EventID:    fmt.Sprintf("%s-%s-%d", eventType, run.ID, time.Now().UnixMilli()),
		EventType:  eventType,
		OccurredAt: time.Now().UTC().Format(time.RFC3339),
		TenantID:   "orchesty",
		Topology:   topo,
		Run:        run,
		Severity:   severity,
		Context:    ctx,
		Message:    message,
	}

	body, err := json.Marshal(envelope)
	if err != nil {
		config.Log.Error(fmt.Errorf("failed to marshal %s event: %v", eventType, err))
		return
	}

	if err := publisher.PublishRoutingKey(amqp.Publishing{
		ContentType: "application/json",
		Body:        body,
	}, routingKey); err != nil {
		config.Log.Error(fmt.Errorf("failed to publish %s event: %v", eventType, err))
	}
}
