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
	BldHeaders(topology storage.Topology, headers http.Header, isHuman bool, isStop bool) (h amqp.Table, c string, d uint8, t time.Time)
	BldHumanTaskHeaders(topology storage.Topology, headers http.Header, stop bool) (h amqp.Table, c string, d uint8, t time.Time)
	BldCounterHeaders(storage.Topology, http.Header) (h amqp.Table, c string, d uint8, t time.Time)
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

// Prefixed pipes headers
const parentID = prefix + "parent-id"
const processID = prefix + "process-id"
const sequenceID = prefix + "sequence-id"
const nodeID = prefix + "node-id"
const nodeName = prefix + "node-name"
const topologyID = prefix + "topology-id"
const topologyName = prefix + "topology-name"
const pfTimeStamp = prefix + "published-timestamp"
const resultCode = prefix + "result-code"
const pfStop = prefix + "stop"
const startingPointInit = prefix + "from-starting-point"

// Others headers
const htype = "type"
const appID = "app_id"

// Human tasks headers
const documentHeader = prefix + "doc-id"

var whiteList = map[string]struct{}{contentType: {}}

func (b *headerBuilder) BldHeaders(topology storage.Topology, headers http.Header, isHuman bool, isStop bool) (h amqp.Table, c string, d uint8, t time.Time) {
	if isHuman {
		return b.BldHumanTaskHeaders(topology, headers, isStop)
	}

	return b.BldProcessHeaders(topology, headers)
}

func (b *headerBuilder) BldCounterHeaders(topology storage.Topology, headers http.Header) (h amqp.Table, c string, d uint8, t time.Time) {
	h, c, d, t = b.BldProcessHeaders(topology, headers)

	h[htype] = "counter_message"
	h[appID] = "starting_point"
	h[nodeID] = "starting_point"
	h[nodeName] = "starting_point"
	h[startingPointInit] = "1"

	return
}

func (b *headerBuilder) BldHumanTaskHeaders(topology storage.Topology, headers http.Header, stop bool) (h amqp.Table, c string, d uint8, t time.Time) {

	h = amqp.Table{
		parentID:       topology.Node.HumanTask.ParentID,
		sequenceID:     topology.Node.HumanTask.SequenceID,
		topologyID:     topology.ID.Hex(),
		topologyName:   topology.Name,
		pfTimeStamp:    time.Now().UTC().Unix() * 1000,
		processID:      topology.Node.HumanTask.ProcessID,
		CorrelationID:  topology.Node.HumanTask.CorrelationID,
		documentHeader: topology.Node.HumanTask.ID.Hex(),
		resultCode:     "0",
	}

	if stop {
		h[pfStop] = "1003"
	}

	return h, topology.Node.HumanTask.ContentType, b.deliveryMode, time.Now().UTC()
}

func (b *headerBuilder) BldProcessHeaders(topology storage.Topology, headers http.Header) (h amqp.Table, c string, d uint8, t time.Time) {
	h = amqp.Table{
		parentID:      "",
		sequenceID:    "1",
		topologyID:    topology.ID.Hex(),
		topologyName:  topology.Name,
		contentType:   jsonType,
		pfTimeStamp:   time.Now().UTC().Unix() * 1000,
		processID:     uuid.New().String(),
		CorrelationID: uuid.New().String(),
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
