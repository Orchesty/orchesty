package rabbitmq

import (
	"testing"

	"github.com/streadway/amqp"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/config"
	"starting-point/pkg/storage"
)

func TestPublish(t *testing.T) {
	c := getConnection()
	c.Connect()
	p := NewPublisher(c, config.Config.Logger)

	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}
	q := GetProcessQueue(topology)
	m := amqp.Publishing{Body: []byte("test"), Headers: amqp.Table{}}

	p.Publish(m, q.Name)
}

func TestClearChannels(t *testing.T) {

	c := getConnection()
	p := NewPublisher(c, config.Config.Logger)

	p.clearChannels()
}

func getConnection() Connection {
	return NewConnection(
		config.Config.RabbitMQ.Hostname,
		int(config.Config.RabbitMQ.Port),
		"",
		config.Config.RabbitMQ.Username,
		config.Config.RabbitMQ.Password,
		config.Config.Logger)
}
