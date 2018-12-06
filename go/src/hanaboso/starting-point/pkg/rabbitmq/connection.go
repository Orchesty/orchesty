package rabbitmq

import (
	"fmt"
	"github.com/streadway/amqp"
	"sync"
	"time"

	log "github.com/sirupsen/logrus"
)

// Connection represents connection
type Connection interface {
	Connect()
	Declare(Queue)
	Disconnect()
	CreateChannel(string) (ch *amqp.Channel)
	GetChannel(string) (ch *amqp.Channel)
	CloseChannel(string)
	ClearChannels()
	GetRestartChan() chan bool
}

type connection struct {
	host        string
	port        int
	user        string
	password    string
	conn        *amqp.Connection
	channels    map[string]*amqp.Channel
	restartChan chan bool
	log         *log.Logger
	lock        sync.Mutex
}

func (c *connection) Connect() {
	if len(c.channels) == 0 {
		c.channels = make(map[string]*amqp.Channel)
	}

	connString := fmt.Sprintf("amqp://%s:%s@%s:%d/", c.user, c.password, c.host, c.port)

	var err error
	c.conn, err = amqp.Dial(connString)

	if err != nil {
		c.log.Error(fmt.Sprintf("Rabbit MQ connection error: %+v", err))
		c.reconnect()
		return
	}

	go func() {
		err := <-c.conn.NotifyClose(make(chan *amqp.Error))

		if err == nil {
			c.restartChan <- false
		}

		c.log.Error(fmt.Sprintf("Rabbit MQ connection close error: %+v", err))

		c.reconnect()
		c.restartChan <- true
	}()

	log.Info(fmt.Sprintf("Rabbit MQ connected to %s", connString))
}

func (c *connection) Declare(q Queue) {

	if c.conn == nil {
		c.log.Error("Connection setup error: not connected.")
		c.Connect()
	}

	if !c.isChannel(q.Name) {
		ch := c.GetChannel(q.Name)
		// Declare queue
		_, err := ch.QueueDeclare(q.Name, q.Durable, q.AutoDelete, q.Exclusive, q.NoWait, q.Args)

		if err != nil {
			c.log.Fatal(fmt.Sprintf("Rabbit MQ queue declare error: %+v", err))
		}
	}

	c.log.Info(fmt.Sprintf("Rabbit MQ queue declare %s", q.Name))
}

func (c *connection) Disconnect() {
	if c.conn != nil {
		c.ClearChannels()
		err := c.conn.Close()

		if err != nil {
			c.log.Error(fmt.Sprintf("Connection closing error: %+v", err))
		}
	}
}

func (c *connection) CreateChannel(name string) (ch *amqp.Channel) {

	ch, err := c.conn.Channel()

	if err != nil {
		c.log.Fatal(fmt.Sprintf("Rabbit MQ channel error: %+v", err))
	}

	c.channels[name] = ch

	return
}

func (c *connection) GetChannel(name string) (ch *amqp.Channel) {
	defer c.lock.Unlock()
	c.lock.Lock()

	if c.isChannel(name) {
		return c.channels[name]
	}

	return c.CreateChannel(name)
}

func (c *connection) GetRestartChan() chan bool {
	return c.restartChan
}

func (c *connection) CloseChannel(name string) {
	ch := c.GetChannel(name)
	err := ch.Close()
	if err != nil {
		c.log.Error(fmt.Sprintf("Connection closing error: %+v", err))
	}

	delete(c.channels, name)
}

func (c *connection) ClearChannels() {
	for k := range c.channels {
		c.CloseChannel(k)
	}
}

func (c *connection) isChannel(n string) (r bool) {
	r = false
	if _, ok := c.channels[n]; ok {
		r = true
	}

	return
}

func (c *connection) reconnect() {
	c.log.Info("Waiting 1s.")
	time.Sleep(time.Second * 1)
	c.Disconnect()
	c.Connect()
}

// NewConnection construct
func NewConnection(host string, port int, user string, password string, log *log.Logger) (r Connection) {
	return &connection{
		host:        host,
		port:        port,
		user:        user,
		password:    password,
		restartChan: make(chan bool),
		log:         log}
}
