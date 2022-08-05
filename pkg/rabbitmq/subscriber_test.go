package rabbitmq

import (
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/streadway/amqp"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestSubscribers_Subscribe(t *testing.T) {
	node := model.Node{
		ID:       "subscribe",
		Messages: make(chan *model.ProcessMessage, 5),
	}
	shards := []model.NodeShard{{
		RabbitMQDSN: "amqp://rabbitmq",
		Index:       1,
		Node:        &node,
	}}

	rabbit := NewRabbitMQ()
	rabbit.Setup("amqp://rabbitmq", shards)
	rabbit.ConnectSubscribers(shards)

	body := "{\"body\":\"{\\\"some\\\":\\\"message\\\"}\", \"headers\":{}}"

	err := tClient.ch.Publish("", queue(shards[0]), false, false, amqp.Publishing{
		Headers:     nil,
		ContentType: "application/json",
		Body:        []byte(body),
	})
	require.Nil(t, err)

	msg := <-node.Messages

	assert.Equal(t, "{\"some\":\"message\"}", string(msg.Body))
	_ = msg.Ack()

	rabbit.CloseSubscribers()
}
