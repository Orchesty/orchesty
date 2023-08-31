package bridge

import (
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/stretchr/testify/assert"
	"testing"
)

type pubType struct{}

func (p pubType) Publish(publishing amqp.Publishing) error {
	return nil
}

func TestLimiter_Parse(t *testing.T) {
	pub := pubType{}
	limiter := newLimiter(pub)

	all := "system2|all;1;1;system|user;1;1;system2|user;2;2"
	dto := model.ProcessMessage{
		Headers: map[string]interface{}{
			enum.Header_LimitKey: all,
		},
	}

	node := node{
		Node: model.Node{
			Application: "system2",
		},
	}
	result := limiter.process(&node, &dto)
	msg := result.Message()

	assert.Equal(t, "system2|all;1;1;system2|user;2;2", msg.GetHeaderOrDefault(enum.Header_LimitKey, ""))
	assert.Equal(t, all, msg.GetHeaderOrDefault(enum.Header_LimitKeyBase, ""))
}
