package rabbitmq

import (
	"github.com/streadway/amqp"
	"log"
	"fmt"
)

type Consumer struct {
	RabbitMq      RabbitMq
	Queue         string
	PrefetchCount int
	PrefetchSize  int
	ConsumerTag   string
	NoAck         bool
	Exclusive     bool
	NoLocal       bool
	NoWait        bool
	channel       *amqp.Channel
}

func (c *Consumer) Consume(callback Callback) {

	if c.channel == nil {
		c.channel = c.RabbitMq.createChannel()
	}

	c.channel.Qos(c.PrefetchCount, c.PrefetchSize, false)
	msg, err := c.channel.Consume(c.Queue, c.ConsumerTag, c.NoAck, c.Exclusive, c.NoLocal, c.NoWait, nil)

	if err != nil {
		log.Fatalln(fmt.Sprintf("Rabbit MQ consumer error: %s", err))
	}

	forever := make(chan bool)
	go callback(msg)
	log.Println("[*] Waiting for messages. To exit press CTRL+C")
	<-forever

}

type Callback func(msg <-chan amqp.Delivery)
