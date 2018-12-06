package utils

import (
	"github.com/google/uuid"
	"github.com/streadway/amqp"
	"net/http"
	"starting-point/pkg/storage"
	"strings"
	"time"
)

// HeaderBuilder represents headerBuilder
type HeaderBuilder interface {
	BldCounterHeaders(storage.Topology, http.Header) amqp.Table
	BldProcessHeaders(storage.Topology, http.Header) amqp.Table
}

type headerBuilder struct {
	deliveryMode int16
}

const prefix = "pf-"

// CorrelationID header
const CorrelationID = prefix + "correlation-id"

// Prefixed pipes headers
const parentID = prefix + "parent-id"
const processID = prefix + "process-id"
const sequenceID = prefix + "sequence-id"
const nodeID = prefix + "node-id"
const nodeName = prefix + "node-name"
const topologyID = prefix + "topology-id"
const topologyName = prefix + "topology-name"
const pfTimeStamp = prefix + "published-timestamp"

// Standard RabbitMq headers
const timeStamp = "timestamp"
const deliveryMode = "delivery-mode"
const htype = "type"
const appID = "app_id"

var whiteList = map[string]struct{}{contentType: {}}

func (b *headerBuilder) BldCounterHeaders(topology storage.Topology, headers http.Header) amqp.Table {
	h := b.BldProcessHeaders(topology, headers)

	h[htype] = "counter_message"
	h[appID] = "starting_point"
	h[nodeID] = "starting_point"
	h[nodeName] = "starting_point"

	return h
}

func (b *headerBuilder) BldProcessHeaders(topology storage.Topology, headers http.Header) amqp.Table {
	h := amqp.Table{
		parentID:      "",
		sequenceID:    "1",
		topologyID:    topology.ID.Hex(),
		topologyName:  topology.Name,
		contentType:   jsonType,
		timeStamp:     time.Now().UTC().String(),
		deliveryMode:  b.deliveryMode,
		pfTimeStamp:   time.Now().UTC().Unix() * 1000,
		processID:     uuid.New().String(),
		CorrelationID: uuid.New().String(),
	}

	arrayFilter(headers, h)

	return h
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
	return &headerBuilder{deliveryMode: deliveryMode}
}
