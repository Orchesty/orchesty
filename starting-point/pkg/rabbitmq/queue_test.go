package rabbitmq

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/config"
	"starting-point/pkg/storage"
)

func TestGetProcessQueue(t *testing.T) {
	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}
	q := GetProcessQueue(topology)

	assert.IsType(t, string(rune(0)), q.Name)
	assert.Equal(t, config.Config.RabbitMQ.QueueDurable, q.Durable)
	assert.Equal(t, false, q.NoWait)
}
