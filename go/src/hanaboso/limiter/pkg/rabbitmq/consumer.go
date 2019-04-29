package rabbitmq

import (
	"limiter/pkg/logger"

	"github.com/streadway/amqp"

	"fmt"
	"os"
	"sync/atomic"
)

var consumerSeq uint64

func uniqueConsumerTag() string {
	return fmt.Sprintf("ctag-%s-%d", os.Args[0], atomic.AddUint64(&consumerSeq, 1))
}

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
	logger        logger.Logger
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
		c.logger.Fatal(fmt.Sprintf("Rabbit MQ channel qos: %s", err), logger.Context{"error": err})
	}

	if c.consumerTag == "" {
		c.consumerTag = uniqueConsumerTag()
	}

	msgs, err := c.getChannel().Consume(c.queue, c.consumerTag, c.noAck, c.exclusive, c.noLocal, c.noWait, nil)

	if err != nil {
		c.logger.Fatal(fmt.Sprintf("Rabbit MQ consumer error: %s", err), logger.Context{"error": err})
	}

	go callback(msgs)

	c.logger.Info("[*] Waiting for messages. To exit press CTRL+C", nil)

	// waiting forever
	if <-c.connection.GetRestartChan() != false {
		c.connection.Setup()
		c.Consume(callback)
	}
}

// Stop cancels consumption
func (c *consumer) Stop() {
	err := c.getChannel().Cancel(c.consumerTag, false)

	if err != nil {
		c.logger.Info(fmt.Sprintf("Consumer cancel error: %s", err), logger.Context{"error": err})
	}

	if c.channelId != -1 {
		c.connection.CloseChannel(c.channelId)
	}
	c.connection.Stop()
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

func NewConsumer(conn Connection, queue string, logger logger.Logger) (c Consumer) {
	return &consumer{connection: conn, queue: queue, channelId: -1, logger: logger}
}
