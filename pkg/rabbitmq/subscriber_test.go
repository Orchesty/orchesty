package rabbitmq

import (
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/config"

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
		RabbitMQDSN: config.RabbitMQ.DSN,
		Index:       1,
		Node:        &node,
	}}

	rabbit := NewRabbitMQ()
	rabbit.Setup(config.RabbitMQ.DSN, shards)
	rabbit.ConnectSubscribers(shards)

	body := "{\"some\":\"message\"}"

	err := tClient.ch.Publish("", queue(shards[0]), false, false, amqp.Publishing{
		Headers:     nil,
		ContentType: "text/plain",
		Body:        []byte(body),
	})
	require.Nil(t, err)

	msg := <-node.Messages
	assert.Equal(t, body, msg.GetBody())
	_ = msg.Ack()

	rabbit.CloseSubscribers()
}
