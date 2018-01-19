package rabbitmq

import (
	"fmt"
	"log"
	"github.com/streadway/amqp"
	"time"
)

type Connection interface {
	AddQueue(Queue)
	AddExchange(Exchange)
	Setup()
	Connect()
	Disconnect()
	CreateChannel() int
	GetChannel(int) (ch *amqp.Channel)
	GetRestartChan() (chan bool)
	PurgeQueue(Queue)
}

type connection struct {
	host        string
	port        int
	user        string
	password    string
	queues      []Queue
	exchanges   []Exchange
	conn        *amqp.Connection
	channels    []*amqp.Channel
	restartChan chan bool
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
		log.Println("Connection setup error: not connected.")
		c.Connect()
	}

	ch, _ := c.conn.Channel()
	defer ch.Close()

	// Declare exchanges
	for _, e := range c.exchanges {
		err := ch.ExchangeDeclare(e.Name, e.Type, e.Durable, e.AutoDelete, e.Internal, e.NoWait, e.Args)

		if err != nil {
			log.Fatalln(fmt.Sprintf("Rabbit MQ exchange declare error: %s", err))
		}

		log.Println(fmt.Sprintf("Rabbit MQ exchange declare %s", e.Name))
	}

	// Bindings exchange to exchange
	for _, e := range c.exchanges {
		for _, b := range e.Bindings {

			err := ch.ExchangeBind(e.Name, b.RoutingKey, b.Exchange, b.NoWait, b.Args)

			if err != nil {
				log.Fatalln(fmt.Sprintf("Rabbit MQ exchange bind error: %s", err))
			}

			log.Println(fmt.Sprintf("Rabbit MQ exchange bind %s to %s", e.Name, b.Exchange))
		}
	}

	// Declare queues
	for _, q := range c.queues {

		_, err := ch.QueueDeclare(q.Name, q.Durable, q.AutoDelete, q.Exclusive, q.NoWait, q.Args)

		if err != nil {
			log.Fatalln(fmt.Sprintf("Rabbit MQ queue declare error: %s", err))
		}

		log.Println(fmt.Sprintf("Rabbit MQ queue declare %s", q.Name))

		for _, b := range q.Bindings {

			err := ch.QueueBind(q.Name, b.RoutingKey, b.Exchange, b.NoWait, b.Args)

			if err != nil {
				log.Fatalln(fmt.Sprintf("Rabbit MQ queue bind error: %s", err))
			}

			log.Println(fmt.Sprintf("Rabbit MQ queue bind %s to exhange %s", q.Name, b.Exchange))
		}
	}
}

func (c *connection) Connect() {
	connString := fmt.Sprintf("amqp://%s:%s@%s:%d/", c.user, c.password, c.host, c.port)

	var err error
	c.conn, err = amqp.Dial(connString)

	if err != nil {
		log.Println(fmt.Sprintf("Rabbit MQ connection error: %s", err))
		c.reconnect()
		return
	}

	go func() {
		log.Println(fmt.Sprintf("Rabbit MQ connection close error: %s", <-c.conn.NotifyClose(make(chan *amqp.Error))))

		c.reconnect()
		c.restartChan <- true
	}()

	// Restore channels
	ids := len(c.channels)
	c.channels = nil
	for i := 0; i < ids; i++ {
		c.CreateChannel()
	}

	log.Println(fmt.Sprintf("Rabbit MQ connected to %s", connString))
}

func (c *connection) Disconnect() {
	if c.conn != nil {
		c.conn.Close()
	}
}

func (c *connection) CreateChannel() int {

	ch, err := c.conn.Channel()

	//@todo add reconnect go routine

	if err != nil {
		log.Fatalln(fmt.Sprintf("Rabbit MQ channel error: %s", err))
	}

	c.channels = append(c.channels, ch)

	return len(c.channels) - 1
}

func (c *connection) GetChannel(id int) (ch *amqp.Channel) {
	return c.channels[id]
}

func (c *connection) reconnect() {
	log.Println("Waiting 1s.")
	time.Sleep(time.Second)
	c.Disconnect()
	c.Connect()
}

func (c *connection) GetRestartChan() (chan bool) {
	return c.restartChan
}

func NewConnection(host string, port int, user string, password string) (r Connection) {
	return &connection{host: host, port: port, user: user, password: password, restartChan: make(chan bool)}
}
