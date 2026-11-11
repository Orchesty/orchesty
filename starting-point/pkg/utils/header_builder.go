package utils

import (
	"time"

	"github.com/google/uuid"
	amqp "github.com/rabbitmq/amqp091-go"
	"starting-point/pkg/storage"
)

// HeaderBuilder represents headerBuilder
type HeaderBuilder interface {
	BuildHeaders(topology storage.Topology) (h amqp.Table, c string, d uint8, t time.Time)
	BldProcessHeaders(storage.Topology) (h amqp.Table, c string, d uint8, t time.Time)
}

type headerBuilder struct {
	deliveryMode uint8
}

// CorrelationID header
const CorrelationID = "correlation-id"

// ApplicationID header
const ApplicationID = "application"

// UserID header
const UserID = "user"
const LimitKey = "limiter-key"
const Applications = "applications"

// Reply headers — when present on the inbound run request they are propagated
// through the whole topology so a response node at the end can POST the final
// payload back to the caller (Pulse / sync callers). ReplyTo is the callback
// URL, ReplyID correlates the response with the original request.
const ReplyTo = "reply-to"
const ReplyID = "reply-id"

// Identity of the real caller behind a run (e.g. a Pulse run). `user` carries
// the system identity (orchesty) for shared ApplicationInstall lookups, so the
// authenticated user's email travels separately here and is propagated through
// the whole topology (registered as a bridge identity header).
const Identity = "identity"

const PulseKey = "pulse-key"
const PulseKeyScope = "pulse:internal"

// NodeID header
const NodeID = "node-id"

// ProcessID header
const ProcessID = "process-id"

// Pipes headers
const parentID = "parent-id"
const sequenceID = "sequence-id"
const topologyID = "topology-id"
const processStarted = "process-started"

const PublishedTimeStamp = "published-timestamp"

func (b *headerBuilder) BuildHeaders(topology storage.Topology) (h amqp.Table, c string, d uint8, t time.Time) {
	return b.BldProcessHeaders(topology)
}

func (b *headerBuilder) BldProcessHeaders(topology storage.Topology) (h amqp.Table, c string, d uint8, t time.Time) {
	h = amqp.Table{
		parentID:       "",
		topologyID:     topology.ID.Hex(),
		contentType:    jsonType,
		ProcessID:      uuid.New().String(),
		CorrelationID:  uuid.New().String(),
		processStarted: Now(),
	}

	return h, h[contentType].(string), b.deliveryMode, time.Now().UTC()
}

// NewHeaderBuilder construct
func NewHeaderBuilder(deliveryMode int16) HeaderBuilder {
	return &headerBuilder{deliveryMode: uint8(deliveryMode)}
}
