package rabbitmq

import (
	"github.com/streadway/amqp"
	"log"
	"fmt"
)

type Callback func(msg <-chan amqp.Delivery)

type Consumer interface {
	Consume(Callback)
}

type consumer struct {
	rabbitMq      RabbitMq
	channel       *amqp.Channel
	queue         string
	PrefetchCount int
	PrefetchSize  int
	ConsumerTag   string
	NoAck         bool
	Exclusive     bool
	NoLocal       bool
	NoWait        bool
}

func (c *consumer) Consume(callback Callback) {

	if c.channel == nil {
		c.channel = c.rabbitMq.createChannel()
	}

	err := c.channel.Qos(c.PrefetchCount, c.PrefetchSize, false)

	if err != nil {
		log.Fatalln(fmt.Sprintf("Rabbit MQ channel qos: %s", err))
	}

	msgs, err := c.channel.Consume(c.queue, c.ConsumerTag, c.NoAck, c.Exclusive, c.NoLocal, c.NoWait, nil)

	if err != nil {
		log.Fatalln(fmt.Sprintf("Rabbit MQ consumer error: %s", err))
	}

	forever := make(chan bool)
	go callback(msgs)
	log.Println("[*] Waiting for messages. To exit press CTRL+C")
	<-forever
}

func NewConsumer(r RabbitMq, queue string) (c Consumer) {
	return &consumer{rabbitMq: r, queue: queue}
}
