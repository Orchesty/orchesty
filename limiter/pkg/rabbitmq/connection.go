package rabbitmq

import (
	"limiter/pkg/logger"

	"github.com/streadway/amqp"

	"fmt"
	"strconv"
	"time"
)

// Connection represents a connection to RabbitMQ broker
type Connection interface {
	AddQueue(Queue)
	AddExchange(Exchange)
	Setup()
	Connect()
	Disconnect()
	CreateChannel() int
	GetChannel(int) (ch *amqp.Channel)
	GetRestartChan() chan bool
	PurgeQueue(Queue)
	CloseChannel(int)
	Stop()
}

type connection struct {
	dsn         string
	queues      []Queue
	exchanges   []Exchange
	conn        *amqp.Connection
	channels    []*amqp.Channel
	restartChan chan bool
	logger      logger.Logger
}

func (c *connection) AddQueue(q Queue) {
	c.queues = append(c.queues, q)
}

func (c *connection) PurgeQueue(q Queue) {
	ch, _ := c.conn.Channel()
	defer ch.Close()

	ch.QueuePurge(q.Name, true)
}

func (c *connection) AddExchange(e Exchange) {
	c.exchanges = append(c.exchanges, e)
}

func (c *connection) Setup() {
	if c.conn == nil {
		c.logger.Error("Connection setup error: not connected.", nil)
		c.Connect()
	}

	ch, _ := c.conn.Channel()
	defer ch.Close()

	// Declare exchanges
	for _, e := range c.exchanges {
		err := ch.ExchangeDeclare(e.Name, e.Type, e.Durable, e.AutoDelete, e.Internal, e.NoWait, e.Args)

		if err != nil {
			c.logger.Fatal(fmt.Sprintf("Rabbit MQ exchange declare error: %s", err), logger.Context{"error": err})
		}

		c.logger.Info(fmt.Sprintf("Rabbit MQ exchange declare %s", e.Name), nil)
	}

	// Bindings exchange to exchange
	for _, e := range c.exchanges {
		for _, b := range e.Bindings {
			err := ch.ExchangeBind(e.Name, b.RoutingKey, b.Exchange, b.NoWait, b.Args)

			if err != nil {
				c.logger.Fatal(fmt.Sprintf("Rabbit MQ exchange bind error: %s", err), logger.Context{"error": err})
			}

			c.logger.Info(fmt.Sprintf("Rabbit MQ exchange bind %s to %s", e.Name, b.Exchange), nil)
		}
	}

	// Declare queues
	for _, q := range c.queues {
		_, err := ch.QueueDeclare(q.Name, q.Durable, q.AutoDelete, q.Exclusive, q.NoWait, q.Args)

		if err != nil {
			c.logger.Fatal(fmt.Sprintf("Rabbit MQ queue declare error: %s", err), logger.Context{"error": err})
		}

		c.logger.Info(fmt.Sprintf("Rabbit MQ queue declare %s", q.Name), nil)

		for _, b := range q.Bindings {
			err := ch.QueueBind(q.Name, b.RoutingKey, b.Exchange, b.NoWait, b.Args)

			if err != nil {
				c.logger.Fatal(fmt.Sprintf("Rabbit MQ queue bind error: %s", err), logger.Context{"error": err})
			}

			c.logger.Info(fmt.Sprintf("Rabbit MQ queue bind %s to exhange %s", q.Name, b.Exchange), nil)
		}
	}
}

func (c *connection) Connect() {
	var err error
	c.conn, err = amqp.Dial(c.dsn)

	if err != nil {
		c.logger.Error(fmt.Sprintf("Rabbit MQ connection error: %s", err), logger.Context{"error": err})
		c.reconnect()
		return
	}

	go func() {
		err := <-c.conn.NotifyClose(make(chan *amqp.Error))

		if err == nil {
			c.restartChan <- false
		}

		c.logger.Error(fmt.Sprintf("Rabbit MQ connection close error: %s", err), logger.Context{"error": err})

		c.reconnect()
		c.restartChan <- true
	}()

	// Restore channels
	ids := len(c.channels)
	c.channels = nil
	for i := 0; i < ids; i++ {
		c.CreateChannel()
	}

	c.logger.Info("Rabbit MQ connected", nil)
}

func (c *connection) Disconnect() {
	if c.conn != nil {
		c.conn.Close()
	}
}

func (c *connection) CreateChannel() int {
	ch, err := c.conn.Channel()

	if err != nil {
		c.logger.Fatal(fmt.Sprintf("Rabbit MQ channel error: %s", err), logger.Context{"error": err})
	}

	c.channels = append(c.channels, ch)

	return len(c.channels) - 1
}

func (c *connection) GetChannel(id int) (ch *amqp.Channel) {
	return c.channels[id]
}

func (c *connection) reconnect() {
	c.logger.Info("Waiting 1s.", nil)
	time.Sleep(time.Second * 1)
	c.Disconnect()
	c.Connect()
}

func (c *connection) GetRestartChan() chan bool {
	return c.restartChan
}

func (c *connection) CloseChannel(id int) {
	c.GetChannel(id).Close()
	c.channels[id] = nil
	c.logger.Info(fmt.Sprintf("Rabbit MQ channel with id %s.", strconv.Itoa(id)), nil)
}

func (c *connection) Stop() {
	exists := false
	for _, ch := range c.channels {
		if ch != nil {
			exists = true
		}
	}

	if !exists && c.conn != nil {
		c.conn.Close()
		c.conn = nil
		c.logger.Info("Rabbit MQ connection close.", nil)
	}
}

// NewConnection creates new Connection struct (disconnected until Connect is called)
func NewConnection(dsn string, logger logger.Logger) (r Connection) {
	return &connection{dsn: dsn, restartChan: make(chan bool), logger: logger}
}
