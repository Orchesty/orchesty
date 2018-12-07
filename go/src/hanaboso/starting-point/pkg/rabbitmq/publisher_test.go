package rabbitmq

import (
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"github.com/streadway/amqp"
	"starting-point/pkg/config"
	"starting-point/pkg/storage"
	"testing"
)

func TestPublish(t *testing.T) {
	c := getConnection()
	c.Connect()
	p := NewPublisher(c, config.Config.Logger)

	topology := storage.Topology{Name: "Topology", ID: objectid.New(), Node: &storage.Node{ID: objectid.New(), Name: "Node"}}
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
		config.Config.RabbitMQ.Username,
		config.Config.RabbitMQ.Password,
		config.Config.Logger)
}
