package rabbitmq

import (
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/stretchr/testify/assert"
)

func TestSetup_Setup(t *testing.T) {
	shard := model.NodeShard{
		RabbitMQDSN: "amqp://rabbitmq",
		Index:       1,
		Node: &model.Node{
			ID: "setup",
		},
	}
	setup(shard.RabbitMQDSN, []model.NodeShard{shard})

	queueName := queue(shard)
	exchangeName := exchange(shard)

	q, _ := tClient.ch.QueueInspect(queueName)
	assert.Equal(t, queueName, q.Name)

	// TODO assertnout, že existuje exchange a queue je nabindovaná

	_, _ = tClient.ch.QueueDelete(queueName, false, false, false)
	_ = tClient.ch.ExchangeDelete(exchangeName, false, false)
}
