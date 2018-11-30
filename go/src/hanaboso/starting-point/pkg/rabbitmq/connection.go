package rabbitmq

import (
	logger "github.com/sirupsen/logrus"
	"github.com/streadway/amqp"

	"fmt"
	"strconv"
	"time"
)

// Connection represents connection
type Connection interface {
	Connect()
	Declare(Queue)
	Disconnect()
	CreateChannel() int
	GetChannel(int) (ch *amqp.Channel)
	GetRestartChan() chan bool
	CloseChannel(int)
	Stop()
}

type connection struct {
	host        string
	port        int
	user        string
	password    string
	conn        *amqp.Connection
	channels    []*amqp.Channel
	restartChan chan bool
	logger      logger.Logger
}

func (c *connection) Connect() {
	connString := fmt.Sprintf("amqp://%s:%s@%s:%d/", c.user, c.password, c.host, c.port)

	var err error
	c.conn, err = amqp.Dial(connString)

	if err != nil {
		c.logger.Error(fmt.Sprintf("Rabbit MQ connection error: %s", err))
		c.reconnect()
		return
	}

	go func() {
		err := <-c.conn.NotifyClose(make(chan *amqp.Error))

		if err == nil {
			c.restartChan <- false
		}

		c.logger.Error(fmt.Sprintf("Rabbit MQ connection close error: %s", err))

		c.reconnect()
		c.restartChan <- true
	}()

	// Restore channels
	ids := len(c.channels)
	c.channels = nil
	for i := 0; i < ids; i++ {
		c.CreateChannel()
	}

	c.logger.Info(fmt.Sprintf("Rabbit MQ connected to %s", connString))
}

func (c *connection) Declare(q Queue) {

	if c.conn == nil {
		c.logger.Error("Connection setup error: not connected.")
		c.Connect()
	}

	ch, _ := c.conn.Channel()

	defer func() {
		err := ch.Close()
		if err != nil {
			c.logger.Error(fmt.Sprintf("Connection closing error: %s", err))
		}
	}()

	// Declare queue
	_, err := ch.QueueDeclare(q.Name, q.Durable, q.AutoDelete, q.Exclusive, q.NoWait, q.Args)

	if err != nil {
		c.logger.Fatal(fmt.Sprintf("Rabbit MQ queue declare error: %s", err))
	}

	c.logger.Info(fmt.Sprintf("Rabbit MQ queue declare %s", q.Name))
}

func (c *connection) Disconnect() {
	if c.conn != nil {
		err := c.conn.Close()

		if err != nil {
			c.logger.Error(fmt.Sprintf("Connection closing error: %s", err))
		}
	}
}

func (c *connection) CreateChannel() int {

	ch, err := c.conn.Channel()

	if err != nil {
		c.logger.Fatal(fmt.Sprintf("Rabbit MQ channel error: %s", err))
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
	err := c.GetChannel(id).Close()
	if err != nil {
		c.logger.Error(fmt.Sprintf("Connection closing error: %s", err))
	}

	c.channels[id] = nil
	c.logger.Info(fmt.Sprintf("Rabbit MQ channel with id %s.", strconv.Itoa(id)))
}

func (c *connection) Stop() {
	exists := false
	for _, ch := range c.channels {
		if ch != nil {
			exists = true
		}
	}

	if exists == false && c.conn != nil {
		err := c.conn.Close()
		if err != nil {
			c.logger.Error(fmt.Sprintf("Connection closing error: %s", err))
		}

		c.conn = nil
		c.logger.Info("Rabbit MQ connection close.")
	}
}

// NewConnection construct
func NewConnection(host string, port int, user string, password string, logger logger.Logger) (r Connection) {
	return &connection{host: host, port: port, user: user, password: password, restartChan: make(chan bool), logger: logger}
}
