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
	for i := 0; i < len(r.exchanges); i++ {
		err := ch.ExchangeDeclare(r.exchanges[i].Name, r.exchanges[i].Type, r.exchanges[i].Durable, r.exchanges[i].AutoDelete, r.exchanges[i].Internal, r.exchanges[i].NoWait, nil)

		if err != nil {
			log.Fatalln(fmt.Sprintf("Rabbit MQ exchange declare error: %s", err))
		}

		log.Println(fmt.Sprintf("Rabbit MQ exchange declare %s", r.exchanges[i].Name))
	}

	// Bindings exchange to exchange
	for i := 0; i < len(r.exchanges); i++ {
		for j := 0; j < len(r.exchanges[i].Bindings); j++ {

			err := ch.ExchangeBind(r.exchanges[i].Name, r.exchanges[i].Bindings[j].RoutingKey, r.exchanges[i].Bindings[j].Exchange, r.exchanges[i].Bindings[j].NoWait, nil)

			if err != nil {
				log.Fatalln(fmt.Sprintf("Rabbit MQ exchange bind error: %s", err))
			}

			log.Println(fmt.Sprintf("Rabbit MQ exchange bind %s to %s", r.exchanges[i].Name, r.exchanges[i].Bindings[j].Exchange))
		}
	}

	// Declare queues
	for i := 0; i < len(r.queues); i++ {

		_, err := ch.QueueDeclare(r.queues[i].Name, r.queues[i].Durable, r.queues[i].AutoDelete, r.queues[i].Exclusive, r.queues[i].NoWait, nil)

		if err != nil {
			log.Fatalln(fmt.Sprintf("Rabbit MQ queue declare error: %s", err))
		}

		log.Println(fmt.Sprintf("Rabbit MQ queue declare %s", r.exchanges[i].Name))

		for j := 0; j < len(r.queues[i].Bindings); j++ {

			err := ch.QueueBind(r.queues[i].Name, r.queues[i].Bindings[j].RoutingKey, r.queues[i].Bindings[j].Exchange, r.queues[i].Bindings[j].NoWait, nil)

			if err != nil {
				log.Fatalln(fmt.Sprintf("Rabbit MQ queue bind error: %s", err))
			}

			log.Println(fmt.Sprintf("Rabbit MQ queue bind %s to exhange %s", r.queues[i].Name, r.queues[i].Bindings[j].Exchange))
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
