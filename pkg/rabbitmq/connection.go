package rabbitmq

import (
	"fmt"
	"sync"
	"time"

	"github.com/streadway/amqp"
	"starting-point/pkg/config"

	log "github.com/sirupsen/logrus"
)

// Connection represents connection
type Connection interface {
	Connect()
	Declare(q *Queue)
	Disconnect()
	CreateChannel(string) (chD ChanData)
	GetChannel(string) (chD ChanData)
	CloseChannel(string)
	ClearChannels()
	GetRestartChan() chan bool
}

// ChanData represents ChanData
type ChanData struct {
	Ch      *amqp.Channel
	Confirm chan amqp.Confirmation
}

type connection struct {
	host        string
	port        int
	user        string
	password    string
	conn        *amqp.Connection
	channels    map[string]ChanData
	restartChan chan bool
	log         *log.Logger
	lock        sync.Mutex
}

func (c *connection) Connect() {
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
		c.restartChan <- true
	}()

	log.Info(fmt.Sprintf("Rabbit MQ connected to %s", connString))

	// clear Channels
	c.ClearChannels()
}

func (c *connection) Declare(q *Queue) {

	if c.conn == nil {
		c.log.Error("Connection setup error: not connected.")
		c.Connect()
	}

	if !c.isChannel(q.Name) {
		ch := c.GetChannel(q.Name)
		// Declare queue
		_, err := ch.Ch.QueueDeclare(q.Name, q.Durable, q.AutoDelete, q.Exclusive, q.NoWait, q.Args)

		if err != nil {
			c.log.Fatal(fmt.Sprintf("Rabbit MQ queue declare error: %+v", err))
		}
		c.log.Info(fmt.Sprintf("Rabbit MQ queue declare %s", q.Name))
	}
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

func (c *connection) GetChannel(name string) (chD ChanData) {
	if c.isChannel(name) {
		return c.saveRead(name)
	}

	return c.CreateChannel(name)
}

func (c *connection) CreateChannel(name string) (chD ChanData) {
	ch, err := c.conn.Channel()
	if err != nil {
		c.log.Fatal(fmt.Sprintf("Rabbit MQ channel error: %+v", err))
	}

	errC := ch.Confirm(false)
	if errC != nil {
		c.log.Error(fmt.Sprintf("confirm.select destination: %+v", errC))
	}

	c.saveWriteChan(name, ChanData{Ch: ch, Confirm: ch.NotifyPublish(make(chan amqp.Confirmation, config.Config.RabbitMQ.MaxConcurrentPublish))})

	return c.saveRead(name)
}

func (c *connection) GetRestartChan() chan bool {
	return c.restartChan
}

func (c *connection) CloseChannel(name string) {
	if c.isChannel(name) {
		ch := c.GetChannel(name)
		err := ch.Ch.Close()
		if err != nil {
			c.log.Error(fmt.Sprintf("Connection closing error: %+v", err))
		}
		c.saveDelete(name)
	}
}

func (c *connection) ClearChannels() {
	for k := range c.channels {
		c.CloseChannel(k)
	}
}

func (c *connection) isChannel(n string) bool {
	c.lock.Lock()
	defer c.lock.Unlock()

	_, ok := c.channels[n]
	return ok
}

func (c *connection) saveWriteChan(name string, ch ChanData) {
	c.lock.Lock()
	defer c.lock.Unlock()

	c.channels[name] = ch
}

func (c *connection) saveRead(name string) ChanData {
	c.lock.Lock()
	defer c.lock.Unlock()

	return c.channels[name]
}

func (c *connection) saveDelete(name string) {
	c.lock.Lock()
	defer c.lock.Unlock()

	delete(c.channels, name)
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
		channels:    make(map[string]ChanData),
		restartChan: make(chan bool),
		log:         log}
}
