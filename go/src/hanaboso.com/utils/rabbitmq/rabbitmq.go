package rabbitmq

import (
	"fmt"
	"log"
	"github.com/streadway/amqp"
)

type RabbitMq struct {
	host      string
	port      int
	user      string
	password  string
	queues    []Queue
	Exchanges []Exchange
	conn      *amqp.Connection
}

func (r *RabbitMq) AddQueue(q Queue) {
	r.queues = append(r.queues, q)
}

func (r *RabbitMq) AddExchange(e Exchange) {
	r.Exchanges = append(r.Exchanges, e)
}

func (r *RabbitMq) Setup() {

	fmt.Println(len(r.Exchanges))
	for i := 0; i < len(r.Exchanges); i++ {
		fmt.Println(r.Exchanges[i])
	}

	for i := 0; i < len(r.Exchanges); i++ {
		for j := 0; j < len(r.Exchanges[i].Bindings); j++ {
			fmt.Println(r.Exchanges[i].Bindings[j])
		}
	}

	fmt.Println(len(r.queues))
	for i := 0; i < len(r.queues); i++ {
		fmt.Println(r.queues[i])

		for j := 0; j < len(r.queues[i].Bindings); j++ {
			fmt.Println(r.queues[i].Bindings[j])
		}
	}
}

func (r *RabbitMq) Connect() {
	connString := fmt.Sprintf("amqp://%s:%s@%s:%d/", r.user, r.password, r.host, r.port)

	var err error
	r.conn, err = amqp.Dial(connString)

	if err != nil {
		log.Fatalln("Rabbit MQ connection error: %s", err)
	}

	log.Println(fmt.Sprintf("Rabbit MQ connected to %s", connString))
}
