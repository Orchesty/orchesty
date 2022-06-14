package rabbit

import (
	"fmt"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/utils/intx"
	"github.com/streadway/amqp"
	"sync"
	"time"
)

type RabbitMq struct {
	address    string
	connection *amqp.Connection
	consumers  map[string]*Consumer
	publishers map[string]*Publisher
	_lock      *sync.RWMutex
}

func NewRabbitMq() *RabbitMq {
	rb := &RabbitMq{
		address:    config.RabbitMq.Dsn,
		connection: nil,
		_lock:      &sync.RWMutex{},
		consumers:  make(map[string]*Consumer, 0),
		publishers: make(map[string]*Publisher, 0),
	}

	go rb.connect()

	return rb
}

func (r *RabbitMq) NewConsumer(queue string) *Consumer {
	if c, ok := r.consumers[queue]; ok {
		return c
	}

	consumer := &Consumer{
		Queue:  queue,
		rabbit: r,
	}

	r.consumers[queue] = consumer

	return consumer
}

func (r *RabbitMq) NewPublisher(exchange, routingKey string) *Publisher {
	key := fmt.Sprintf("%s__%s", exchange, routingKey)
	if c, ok := r.publishers[key]; ok {
		return c
	}

	publisher := &Publisher{
		Exchange:   exchange,
		RoutingKey: routingKey,
		rabbit:     r,
	}

	r.publishers[key] = publisher
	publisher.connect()

	return publisher
}

func (r *RabbitMq) Stop() {
	for _, consumer := range r.consumers {
		consumer.stop()
	}
	for _, publisher := range r.publishers {
		publisher.stop()
	}
	if err := r.connection.Close(); err != nil {
		config.Log.Error(err)
	}
}

func (r *RabbitMq) connect() {
	r._lock.Lock()
	defer r._lock.Unlock()
	if r.connection != nil && !r.connection.IsClosed() {
		return
	}

	reconnectDelay := 2
	for {
		config.Log.Debug("connecting to rabbitMQ: %s", r.address)
		conn, err := amqp.Dial(r.address)
		if err != nil {
			config.Log.Debug("failed connecting to RabbitMQ server: %v", err)

			<-time.After(time.Duration(reconnectDelay) * time.Second)
			reconnectDelay = intx.Min(reconnectDelay+2, 30)
			continue
		}

		r.connection = conn
		return
	}
}

func (r *RabbitMq) channel() *amqp.Channel {
	for {
		if r.connection == nil {
			r.connect()
		}

		ch, err := r.connection.Channel()
		if err != nil {
			r.connect()
			continue
		}

		return ch
	}
}
