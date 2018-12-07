package rabbitmq

import (
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"github.com/stretchr/testify/assert"
	"starting-point/pkg/config"
	"starting-point/pkg/storage"
	"testing"
)

func TestGetProcessCounterQueue(t *testing.T) {
	q := GetProcessCounterQueue()

	assert.Equal(t, config.Config.RabbitMQ.CounterQueueName, q.Name)
	assert.Equal(t, config.Config.RabbitMQ.CounterQueueDurable, q.Durable)
	assert.Equal(t, false, q.NoWait)
}

func TestGetProcessQueue(t *testing.T) {
	topology := storage.Topology{Name: "Topology", ID: objectid.New(), Node: &storage.Node{ID: objectid.New(), Name: "Node"}}
	q := GetProcessQueue(topology)

	assert.IsType(t, string(0), q.Name)
	assert.Equal(t, config.Config.RabbitMQ.QueueDurable, q.Durable)
	assert.Equal(t, false, q.NoWait)
}
