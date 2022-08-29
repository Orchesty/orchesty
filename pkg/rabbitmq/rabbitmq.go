package rabbitmq

import (
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/rs/zerolog/log"
	"sync"
	"time"
)

// Public RabbitMQ service - this should be the only visible struct through which bridge connects nodes
type RabbitMQ struct {
	subscribers []*subscribers
	publishers  []*publishers
	Repeater    *publisher
	Counter     *publisher
	Limiter     *publisher
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

func (rabbit *RabbitMQ) AwaitStartup() {
	for _, subs := range rabbit.subscribers {
		for _, sub := range subs.subscribers {
			for sub.channel == nil {
				time.Sleep(10 * time.Millisecond)
			}
		}
	}
	for _, pubs := range rabbit.publishers {
		for _, pub := range pubs.publishers {
			for pub.channel == nil {
				time.Sleep(10 * time.Millisecond)
			}
		}
	}
}

// Returns *bridge.Publisher interface, but is an unexported return - seems to be ok by Go' standards
func (rabbit *RabbitMQ) GetPublisher(nodeId string) *publisher {
	exchange := exchange(model.NodeShard{Node: &model.Node{ID: nodeId}})
	for _, publishers := range rabbit.publishers {
		for _, publisher := range publishers.publishers {
			if publisher.exchange == exchange {
				return publisher
			}
		}
	}

	log.Fatal().Msgf("unknown publisher by exchange [%s]", exchange)
	panic("unreachable")
}

func (rabbit *RabbitMQ) DeleteQueues() {
	log.Debug().Msg("removing rabbitMq queues and exchanges...")
	for _, subscribers := range rabbit.subscribers {
		conn, _ := amqp.Dial(subscribers.address)
		ch, _ := conn.Channel()
		for _, subscriber := range subscribers.subscribers {
			if _, err := ch.QueueDelete(subscriber.queue, false, false, false); err != nil {
				log.Error().Err(err).Send()
			}
			if err := ch.ExchangeDelete(subscriber.exchange, false, false); err != nil {
				log.Error().Err(err).Send()
			}
		}
		_ = ch.Close()
		_ = conn.Close()
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

func (rabbit *RabbitMQ) SetupLimitRepeat(address string) {
	// TODO dočasný ohejbák
	limiter := &publisher{
		routingKey:    "pipes.limiter",
		exchange:      "",
		notifyConfirm: make(chan amqp.Confirmation, 1),
		mu:            &sync.Mutex{},
	}
	counter := &publisher{
		routingKey:    "pipes.multi-counter",
		exchange:      "",
		notifyConfirm: make(chan amqp.Confirmation, 1),
		mu:            &sync.Mutex{},
	}
	repeater := &publisher{
		routingKey:    "pipes.repeater",
		exchange:      "",
		notifyConfirm: make(chan amqp.Confirmation, 1),
		mu:            &sync.Mutex{},
	}
	pubs := &publishers{
		publishers: []*publisher{
			limiter,
			repeater,
			counter,
		},
		wg: &sync.WaitGroup{},
	}
	conn, _ := amqp.Dial(address)
	ch, _ := conn.Channel()
	declareQueue(ch, "pipes.limiter")
	declareQueue(ch, "pipes.multi-counter")
	declareQueue(ch, "pipes.repeater")

	go pubs.handleReconnect(pubs, address)

	rabbit.Limiter = limiter
	rabbit.Repeater = repeater
	rabbit.Counter = counter
}

func exchange(shard model.NodeShard) string {
	return fmt.Sprintf("node.%s.hx", shard.Node.ID)
}

func queue(shard model.NodeShard) string {
	return fmt.Sprintf("node.%s.%d", shard.Node.ID, shard.Index)
}

func routingKey(_ model.NodeShard) string {
	return "1" // TODO tohle se rozsype pokud se přidá různorodost při recreate / rebind
}
