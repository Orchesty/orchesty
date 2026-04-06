package model

import "context"

type EvaluatorHelpers struct {
	WindowCount func(ctx context.Context, key string, windowMs int) (int64, error)
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

type DispatchPayload struct {
	PresetID   string        `json:"preset_id"`
	TenantID   string        `json:"tenant_id"`
	Channel    string        `json:"channel"`
	Event      EventEnvelope `json:"event"`
	Recipients []string      `json:"recipients"`
}
