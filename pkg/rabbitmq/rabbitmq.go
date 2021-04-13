package rabbitmq

import (
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
)

// Public RabbitMQ service - this should be the only visible struct through which bridge connects nodes
type RabbitMQ struct {
	subscribers []*subscribers
	publishers  []*publishers
}

// ConnectSubscriber to rabbitmq
func (rabbit *RabbitMQ) ConnectSubscribers(shards []model.NodeShard) {
	rabbit.subscribers = append(rabbit.subscribers, newSubscribers(shards))
}

// ConnectPublisher to rabbitmq
func (rabbit *RabbitMQ) ConnectPublishers(shards []model.NodeShard) {
	rabbit.publishers = append(rabbit.publishers, newPublishers(shards))
}

func (rabbit *RabbitMQ) CloseSubscribers() {
	log.Debug().Msg("stopping rabbitMq subscribers...")
	for _, subscriber := range rabbit.subscribers {
		subscriber.close()
	}
}

func (rabbit *RabbitMQ) ClosePublishers() {
	log.Debug().Msg("stopping rabbitMq publishers...")
	for _, publisher := range rabbit.publishers {
		publisher.close()
	}
}

func (rabbit *RabbitMQ) Setup(address string, shards []model.NodeShard) {
	setup(address, shards)
}

func NewRabbitMQ() *RabbitMQ {
	return &RabbitMQ{
		subscribers: make([]*subscribers, 0),
		publishers:  make([]*publishers, 0),
	}
}

func exchange(shard model.NodeShard) string {
	return fmt.Sprintf("node.%s.hx", shard.Node.ID)
}

func queue(shard model.NodeShard) string {
	return fmt.Sprintf("node.%s.%d", shard.Node.ID, shard.Index)
}

func routingKey(_ model.NodeShard) string {
	return "1"
}
