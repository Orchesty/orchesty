package rabbitmq

import (
	amqp "github.com/rabbitmq/amqp091-go"
	"starting-point/pkg/config"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"
)

// Queue struct of queue
type Queue struct {
	Name       string
	Durable    bool
	AutoDelete bool
	Exclusive  bool
	NoWait     bool
	Args       amqp.Table
}

// GetProcessQueue returns Queue conf
func GetProcessQueue(topology storage.Topology) *Queue {
	queueName := utils.GenerateTplgName(topology)

	return &Queue{Name: queueName, Durable: config.Config.RabbitMQ.QueueDurable, NoWait: false}
}
