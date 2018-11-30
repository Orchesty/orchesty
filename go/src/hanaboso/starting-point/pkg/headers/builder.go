package headers

import (
	"github.com/google/uuid"
	"github.com/streadway/amqp"
	"time"
)

// Builder represents builder
type Builder interface {
	BldCounterHeaders(topology Topology) amqp.Table
	BldHeaders()
}

type builder struct {
	deliveryMode string
}

// Topology temporary remove it
type Topology struct {
	ID   string
	Name string
}

const parentID = "pf-parent-id"
const correlationID = "pf-correlation-id"
const processID = "pf-process-id"
const sequenceID = "pf-sequence-id"
const nodeID = "pf-node-id"
const nodeName = "pf-node-name"
const topologyID = "pf-topology-id"
const topologyName = "pf-topology-name"
const topologyDeleteURL = "pf-topology-delete-url"
const resultCode = "pf-result-code"
const resultMessage = "pf-result-message"
const resultDetail = "pf-result-detail"
const repeatQueue = "pf-repeat-queue"
const repeatInterval = "pf-repeat-interval"
const repeatMaxHops = "pf-repeat-max-hops"
const repeatHops = "pf-repeat-hops"
const contentType = "content-type"
const timeStamp = "timestamp"
const pfTimeStamp = "pf-published-timestamp"
const deliveryMode = "delivery-mode"

func (b *builder) BldCounterHeaders(topology Topology) amqp.Table {
	h := amqp.Table{
		parentID:      "",
		sequenceID:    "1",
		topologyID:    topology.ID,
		topologyName:  topology.Name,
		contentType:   "application/json",
		timeStamp:     time.Now().UTC().String(),
		deliveryMode:  b.deliveryMode,
		pfTimeStamp:   time.Now().UTC().Unix() * 1000,
		processID:     uuid.New().String(),
		correlationID: uuid.New().String(),
	}

	return h
}

func (b *builder) BldHeaders() {

}

// NewBuilder construct
func NewBuilder(deliveryMode string) Builder {
	return &builder{deliveryMode: deliveryMode}
}
