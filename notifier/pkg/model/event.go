package model

type EventEnvelope struct {
	EventID    string         `json:"event_id"`
	EventType  string         `json:"event_type"`
	OccurredAt string         `json:"occurred_at"`
	TenantID   string         `json:"tenant_id"`
	Topology   *TopologyRef   `json:"topology,omitempty"`
	Node       *NodeRef       `json:"node,omitempty"`
	Run        *RunRef        `json:"run,omitempty"`
	Severity   string         `json:"severity"`
	Context    map[string]any `json:"context,omitempty"`
	Message    string         `json:"message,omitempty"`
}

type TopologyRef struct {
	ID   string   `json:"id"`
	Name string   `json:"name"`
	Tags []string `json:"tags,omitempty"`
}

type NodeRef struct {
	ID   string `json:"id"`
	Name string `json:"name"`
}

type RunRef struct {
	ID         string `json:"id"`
	DurationMs int64  `json:"duration_ms,omitempty"`
	Retries    int    `json:"retries,omitempty"`
}
