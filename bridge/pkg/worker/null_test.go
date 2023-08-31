package worker

import (
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/stretchr/testify/assert"
	"strconv"
	"testing"
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
