package worker

import (
	"encoding/json"
	"strconv"
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/stretchr/testify/assert"
)

func TestBroadcastAfterProcess_AfterProcessAll(t *testing.T) {
	worker := Null{}
	_, p := worker.AfterProcess(nullNode{}, prepDto())

	assert.Equal(t, 3, p)
}

func TestBroadcastAfterProcess_AfterProcessOld(t *testing.T) {
	worker := Null{}
	dto := prepDto()
	dto.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_ForwardToQueue))
	dto.SetHeader(enum.Header_ForceTargetQueue, "pipes.topologyId.node2")

	_, p := worker.AfterProcess(nullNode{}, dto)

	assert.Equal(t, 1, p)
}

func TestBroadcastAfterProcess_AfterProcessNew(t *testing.T) {
	worker := Null{}
	dto := prepDto()
	dto.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_ForwardToQueue))
	dto.SetHeader(enum.Header_ForceTargetQueue, "node1,node3")

	_, p := worker.AfterProcess(nullNode{}, dto)

	assert.Equal(t, 2, p)
}

func prepDto() *model.ProcessMessage {
	return &model.ProcessMessage{
		Body:    []byte(""),
		Headers: make(map[string]interface{}),
		Ack: func() error {
			return nil
		},
		Nack: func() error {
			return nil
		},
	}
}

type nullNode struct{}

func (n nullNode) Followers() types.Publishers {
	return map[string]types.Publisher{
		"node1": testPublisher{},
		"node2": testPublisher{},
		"node3": testPublisher{},
	}
}

func (n nullNode) WorkerType() enum.WorkerType {
	return enum.WorkerType_Null
}

func (n nullNode) Settings() model.NodeSettings {
	panic("implement me")
}

func (n nullNode) Id() string {
	return "node"
}

func (n nullNode) Application() string {
	return ""
}

func (n nullNode) Sdk() string {
	return ""
}

func (n nullNode) NodeName() string {
	return "node"
}

func (n nullNode) TopologyName() string {
	return "topology"
}

func (n nullNode) CursorPublisher() types.Publisher {
	return testPublisher{}
}

type testPublisher struct{}

func (t testPublisher) Publish(amqp.Publishing) error {
	return nil
}

// capturingPublisher decodes each Publishing into the inner MessageDto and
// stores the per-message header map. Used by tests that assert on what the
// bridge actually puts on the wire (vs. what stays on the local dto).
type capturingPublisher struct {
	headers []map[string]interface{}
	bodies  []string
}

func (c *capturingPublisher) Publish(p amqp.Publishing) error {
	var msg model.MessageDto
	if err := json.Unmarshal(p.Body, &msg); err != nil {
		return err
	}
	c.headers = append(c.headers, msg.Headers)
	c.bodies = append(c.bodies, msg.Body)
	return nil
}

// capturingNode is a single-follower node backed by a capturingPublisher so
// tests can introspect every published partial.
type capturingNode struct {
	publisher *capturingPublisher
}

func newCapturingNode() *capturingNode {
	return &capturingNode{publisher: &capturingPublisher{}}
}

func (n *capturingNode) Followers() types.Publishers {
	return map[string]types.Publisher{"node1": n.publisher}
}

func (n *capturingNode) WorkerType() enum.WorkerType {
	return enum.WorkerType_Batch
}

func (n *capturingNode) Settings() model.NodeSettings {
	panic("implement me")
}

func (n *capturingNode) Id() string {
	return "node"
}

func (n *capturingNode) Application() string {
	return ""
}

func (n *capturingNode) Sdk() string {
	return ""
}

func (n *capturingNode) NodeName() string {
	return "node"
}

func (n *capturingNode) TopologyName() string {
	return "topology"
}

func (n *capturingNode) CursorPublisher() types.Publisher {
	return testPublisher{}
}
