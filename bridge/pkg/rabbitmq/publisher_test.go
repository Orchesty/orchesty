package rabbitmq

import (
	"encoding/json"
	"testing"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestPublishers_StartPublisher(t *testing.T) {
	shards := []model.NodeShard{{
		RabbitMQDSN: "amqp://rabbitmq",
		Index:       1,
		Node: &model.Node{
			ID:       "publish",
			Messages: make(chan *model.ProcessMessage, 5),
		},
	}}

	rabbit := NewRabbitMQ()
	rabbit.Setup("amqp://rabbitmq", shards)
	rabbit.ConnectPublishers(shards)

	pub := rabbit.publishers[0].publishers[0]

	time.Sleep(time.Second)

	pm := model.ProcessMessage{
		Headers: map[string]interface{}{"foo": "bar"},
		Body:    []byte("{}"),
	}

	require.Nil(t, pub.Publish(pm.IntoAmqp()))

	msgs, err := tClient.ch.Consume(queue(shards[0]), "", true, false, false, false, nil)
	require.Nil(t, err)

	msg := <-msgs

	var toAssert model.MessageDto
	_ = json.Unmarshal(msg.Body, &toAssert)

	assert.Equal(t, "bar", toAssert.Headers["foo"])
	assert.Equal(t, string(pm.Body), toAssert.Body)

	rabbit.ClosePublishers()
}
