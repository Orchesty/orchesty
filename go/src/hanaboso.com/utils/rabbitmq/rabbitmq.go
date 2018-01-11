package rabbitmq

import (
	"fmt"
	"log"
	"github.com/streadway/amqp"
)

type RabbitMq struct {
	Host      string
	Port      int
	User      string
	Password  string
	queues    []Queue
	exchanges []Exchange
	conn      *amqp.Connection
}

func (r *RabbitMq) AddQueue(q Queue) {
	r.queues = append(r.queues, q)
}

func (r *RabbitMq) AddExchange(e Exchange) {
	r.exchanges = append(r.exchanges, e)
}

func (r *RabbitMq) Setup() {

	if r.conn == nil {
		log.Fatalln("RabbitMq setup error: not connected.")
	}

	ch := r.createChannel()
	defer ch.Close()

	// Declare exchanges
	for _, e := range r.exchanges {
		err := ch.ExchangeDeclare(e.Name, e.Type, e.Durable, e.AutoDelete, e.Internal, e.NoWait, nil)

		if err != nil {
			log.Fatalln(fmt.Sprintf("Rabbit MQ exchange declare error: %s", err))
		}

		log.Println(fmt.Sprintf("Rabbit MQ exchange declare %s", e.Name))
	}

	// Bindings exchange to exchange
	for _, e := range r.exchanges {
		for _, b := range e.Bindings {

			err := ch.ExchangeBind(e.Name, b.RoutingKey, b.Exchange, b.NoWait, nil)

			if err != nil {
				log.Fatalln(fmt.Sprintf("Rabbit MQ exchange bind error: %s", err))
			}

			log.Println(fmt.Sprintf("Rabbit MQ exchange bind %s to %s", e.Name, b.Exchange))
		}
	}

	// Declare queues
	for _, q := range r.queues {

		_, err := ch.QueueDeclare(q.Name, q.Durable, q.AutoDelete, q.Exclusive, q.NoWait, nil)

		if err != nil {
			log.Fatalln(fmt.Sprintf("Rabbit MQ queue declare error: %s", err))
		}

		log.Println(fmt.Sprintf("Rabbit MQ queue declare %s", q.Name))

		for _, b := range q.Bindings {

			err := ch.QueueBind(q.Name, b.RoutingKey, b.Exchange, b.NoWait, nil)

			if err != nil {
				log.Fatalln(fmt.Sprintf("Rabbit MQ queue bind error: %s", err))
			}

			log.Println(fmt.Sprintf("Rabbit MQ queue bind %s to exhange %s", q.Name,b.Exchange))
		}
	}
}

func (r *RabbitMq) Connect() {
	connString := fmt.Sprintf("amqp://%s:%s@%s:%d/", r.User, r.Password, r.Host, r.Port)

	var err error
	r.conn, err = amqp.Dial(connString)

	if err != nil {
		log.Fatalln(fmt.Sprintf("Rabbit MQ connection error: %s", err))
	}

	log.Println(fmt.Sprintf("Rabbit MQ connected to %s", connString))
}

func (r *RabbitMq) Disconnect() {
	if r.conn != nil {
		r.conn.Close()
	}
}

func (r *RabbitMq) createChannel() (ch *amqp.Channel) {

	ch, err := r.conn.Channel()

	if err != nil {
		log.Fatalln(fmt.Sprintf("Rabbit MQ channel error: %s", err))
	}

	return ch
}
