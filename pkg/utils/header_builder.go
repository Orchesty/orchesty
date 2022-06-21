package utils

import (
	"net/http"
	"strings"
	"time"

	"github.com/google/uuid"
	"github.com/streadway/amqp"
	"starting-point/pkg/storage"
)

// HeaderBuilder represents headerBuilder
type HeaderBuilder interface {
	BldHeaders(topology storage.Topology, headers http.Header) (h amqp.Table, c string, d uint8, t time.Time)
	BldProcessHeaders(storage.Topology, http.Header) (h amqp.Table, c string, d uint8, t time.Time)
}

type headerBuilder struct {
	deliveryMode uint8
}

const prefix = "pf-"

// CorrelationID header
const CorrelationID = prefix + "correlation-id"

// ApplicationID header
const ApplicationID = prefix + "application"

// UserID header
const UserID = prefix + "user"

// NodeID header
const NodeID = prefix + "node-id"

// ProcessID header
const ProcessID = prefix + "process-id"

// Pipes headers
const parentID = prefix + "parent-id"
const sequenceID = prefix + "sequence-id"
const topologyID = prefix + "topology-id"
const pfTimeStamp = prefix + "published-timestamp"
const processStarted = prefix + "process-started"

var whiteList = map[string]struct{}{contentType: {}}

func (b *headerBuilder) BldHeaders(topology storage.Topology, headers http.Header) (h amqp.Table, c string, d uint8, t time.Time) {
	return b.BldProcessHeaders(topology, headers)
}

func (b *headerBuilder) BldProcessHeaders(topology storage.Topology, headers http.Header) (h amqp.Table, c string, d uint8, t time.Time) {
	h = amqp.Table{
		parentID:       "",
		sequenceID:     "1",
		topologyID:     topology.ID.Hex(),
		contentType:    jsonType,
		pfTimeStamp:    now(),
		ProcessID:      uuid.New().String(),
		CorrelationID:  uuid.New().String(),
		processStarted: now(),
	}

	arrayFilter(headers, h)

	return h, h[contentType].(string), b.deliveryMode, time.Now().UTC()
}

func arrayFilter(h http.Header, t amqp.Table) {
	for key, item := range h {
		key = strings.ToLower(key)
		if strings.HasPrefix(key, prefix) {
			t[key] = item[0]
		} else if _, ok := whiteList[key]; ok {
			t[key] = item[0]
		}
	}
}

// NewHeaderBuilder construct
func NewHeaderBuilder(deliveryMode int16) HeaderBuilder {
	return &headerBuilder{deliveryMode: uint8(deliveryMode)}
}
