package model

import (
	"context"
	"time"

	"go.mongodb.org/mongo-driver/v2/bson"
)

type InAppNotification struct {
	ID           bson.ObjectID `bson:"_id,omitempty" json:"id"`
	TenantID     string        `bson:"tenantId" json:"tenant_id"`
	EventType    string        `bson:"eventType" json:"event_type"`
	Severity     string        `bson:"severity" json:"severity"`
	Message      string        `bson:"message" json:"message"`
	TopologyID   string        `bson:"topologyId,omitempty" json:"topology_id,omitempty"`
	TopologyName string        `bson:"topologyName,omitempty" json:"topology_name,omitempty"`
	NodeName     string        `bson:"nodeName,omitempty" json:"node_name,omitempty"`
	CreatedAt    time.Time     `bson:"createdAt" json:"created_at"`
}

func NewInAppNotification(e EventEnvelope) InAppNotification {
	n := InAppNotification{
		TenantID:  e.TenantID,
		EventType: e.EventType,
		Severity:  e.Severity,
		Message:   e.Message,
		CreatedAt: time.Now(),
	}

	if e.Topology != nil {
		n.TopologyID = e.Topology.ID
		n.TopologyName = e.Topology.Name
	}

	if e.Node != nil {
		n.NodeName = e.Node.Name
	}

	return n
}

type EvaluatorHelpers struct {
	WindowCount func(ctx context.Context, key string, windowSec int) (int64, error)
}

type Preset struct {
	ID          string
	Enabled     bool
	Description string
	Match       func(ctx context.Context, e EventEnvelope, h EvaluatorHelpers) (bool, error)
}

type NotificationMessage struct {
	PresetID string        `json:"preset_id"`
	Event    EventEnvelope `json:"event"`
}

type ChannelRecipients struct {
	Channel    string
	Recipients []string
}

type BufferedEvent struct {
	NodeName     string `json:"node_name"`
	ErrorMessage string `json:"error_message"`
}

type DispatchPayload struct {
	PresetID string `json:"preset_id"`
	// InstanceID is the cloud-side instance UUID (separate concept from
	// tenant_id) injected by the notifier from its deployment env so the
	// downstream cloud-backend can resolve the Instance row directly.
	// Omitted on on-prem deployments where it is not configured.
	InstanceID string          `json:"instance_id,omitempty"`
	TenantID   string          `json:"tenant_id"`
	Channel    string          `json:"channel"`
	Event      EventEnvelope   `json:"event"`
	Events     []BufferedEvent `json:"events,omitempty"`
	Recipients []string        `json:"recipients"`
}
