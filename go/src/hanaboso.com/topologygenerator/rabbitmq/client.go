package rabbitmq

import (
	"github.com/streadway/amqp"
	"fmt"
	"log"
	"hanaboso.com/topologygenerator/model"
)

// Callback function
type Callback func(msgs <-chan amqp.Delivery)

//Queue definition
type Queue struct {
	Name       string
	Durable    bool
	AutoDelete bool
	Exclusive  bool
	NoWait     bool
	Args       amqp.Table
	Callback   model.CallbackFunction
}

// RabbitMQ
type RabbitMq struct {
	conn *amqp.Connection
	user string
	pass string
	host string
	port int
}

func CreateConnection(host string, port int, user string, password string) (conn *RabbitMq) {
	conn = new(RabbitMq)
	conn.host = host
	conn.port = port
	conn.user = user
	conn.pass = password
	conn.connect()

	return conn
}

func (r *RabbitMq) connect() {
	var (
		err error
		uri string
	)
	uri = fmt.Sprintf("amqp://%s:%s@%s:%d/", r.user, r.pass, r.host, r.port)
	r.conn, err = amqp.Dial(uri)

	if err != nil {
		//TODO: handle
	}
}

func (r *RabbitMq) GetChannel() (*amqp.Channel, error) {
	return r.conn.Channel()
}

type Qos struct {
	PrefetchCount int
	PrefetchSize  int
	Global        bool
}

func (r *RabbitMq) Consumer(queue Queue, qos Qos) {
	var (
		q    amqp.Queue
		ch   *amqp.Channel
		err  error
		msgs <-chan amqp.Delivery
	)

	ch, err = r.GetChannel()

	if err != nil {
		//TODO: handle
	}

	q, err = ch.QueueDeclare(
		queue.Name,
		queue.Durable,
		queue.AutoDelete,
		queue.Exclusive,
		queue.NoWait,
		queue.Args,
	)

	if err != nil {
		//TODO: handle
	}

	ch.Qos(qos.PrefetchCount, qos.PrefetchSize, qos.Global)

	msgs, err = ch.Consume(
		q.Name, // queue
		"",     // consumer
		false,  // auto-ack
		false,  // exclusive
		false,  // no-local
		false,  // no-wait
		nil,    // args
	)

	if err != nil {
		//TODO: handle
	}

	forever := make(chan bool)
	go queue.Callback.Handle(msgs)

	log.Printf(" [*] Waiting for messages. To exit press CTRL+C")
	<-forever
}
