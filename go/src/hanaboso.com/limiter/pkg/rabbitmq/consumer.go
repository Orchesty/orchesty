package rabbitmq

import (
	"github.com/streadway/amqp"
	"log"
	"fmt"
)

type Callback func(msg <-chan amqp.Delivery)

type Consumer interface {
	Consume(Callback)
	Stop()
	SetPrefetchCount(int)
	SetPrefetchSize(int)
	SetConsumerTag(string string)
	SetNoAck(bool)
	SetExclusive(bool)
	SetNoLocal(bool)
	SetNoWait(bool)
}

type consumer struct {
	connection    Connection
	channelId     int
	queue         string
	prefetchCount int
	prefetchSize  int
	consumerTag   string
	noAck         bool
	exclusive     bool
	noLocal       bool
	noWait        bool
}

func (c *consumer) getChannel() *amqp.Channel {
	if c.channelId == -1 {
		c.channelId = c.connection.CreateChannel()
	}

	return c.connection.GetChannel(c.channelId)
}

func (c *consumer) Consume(callback Callback) {

	if c.channelId == -1 {
		c.channelId = c.connection.CreateChannel()
	}

	err := c.getChannel().Qos(c.prefetchCount, c.prefetchSize, false)

	if err != nil {
		log.Fatalln(fmt.Sprintf("Rabbit MQ channel qos: %s", err))
	}

	msgs, err := c.getChannel().Consume(c.queue, c.consumerTag, c.noAck, c.exclusive, c.noLocal, c.noWait, nil)

	if err != nil {
		log.Fatalln(fmt.Sprintf("Rabbit MQ consumer error: %s", err))
	}

	go callback(msgs)

	log.Println("[*] Waiting for messages. To exit press CTRL+C")

	// waiting forever
	if <-c.connection.GetRestartChan() != false {
		c.connection.Setup()
		c.Consume(callback)
	}
}

// Stop cancels consumption
func (c *consumer) Stop() {
	c.getChannel().Cancel(c.consumerTag, true)
}

func (c *consumer) SetPrefetchCount(count int) {
	c.prefetchCount = count
}

func (c *consumer) SetPrefetchSize(size int) {
	c.prefetchSize = size
}

func (c *consumer) SetConsumerTag(tag string) {
	c.consumerTag = tag
}

func (c *consumer) SetNoAck(noAck bool) {
	c.noAck = noAck
}

func (c *consumer) SetExclusive(exclusive bool) {
	c.exclusive = exclusive
}

func (c *consumer) SetNoLocal(noLocal bool) {
	c.noLocal = noLocal
}

func (c *consumer) SetNoWait(noWait bool) {
	c.noWait = noWait
}

func NewConsumer(conn Connection, queue string) (c Consumer) {
	return &consumer{connection: conn, queue: queue, channelId: -1}
}
