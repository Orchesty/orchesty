package utils

import (
	"time"

	"github.com/google/uuid"
	amqp "github.com/rabbitmq/amqp091-go"
	"starting-point/pkg/storage"
)

// HeaderBuilder represents headerBuilder
type HeaderBuilder interface {
	BldHeaders(topology storage.Topology) (h amqp.Table, c string, d uint8, t time.Time)
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

func (b *headerBuilder) BldHeaders(topology storage.Topology) (h amqp.Table, c string, d uint8, t time.Time) {
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
