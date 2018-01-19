package client

import (
	"bytes"
	"log"
	"time"

	"github.com/streadway/amqp"
	"fmt"
)

func failOnError(err error, msg string) {
	if err != nil {
		log.Fatalf("%s: %s", msg, err)
	}
}

func CreateConnection(host string, port int, user string, password string) (conn *RabbitMq) {
	conn = new(RabbitMq)
	conn.host = host
	conn.port = port
	conn.user = user
	conn.pass = password
	conn.connect()

	return conn
}

type Callback func(msgs <-chan amqp.Delivery)

type Queue struct {
	Name       string
	Durable    bool
	AutoDelete bool
	Exclusive  bool
	NoWait     bool
	Args       amqp.Table
}

type RabbitMq struct {
	conn *amqp.Connection
	user string
	pass string
	host string
	port int
}

func (r *RabbitMq) connect() {
	var (
		err        error
		connString string
	)
	connString = fmt.Sprintf("amqp://%s:%s@%s:%d/", r.user, r.pass, r.host, r.port)

	fmt.Println(connString)
	r.conn, err = amqp.Dial(connString)

	failOnError(err, "Failed to connect to RabbitMQ")
}

func (r *RabbitMq) GetChannel() (*amqp.Channel, error) {
	return r.conn.Channel()
}

func (r *RabbitMq) Consumer(queue Queue, callback Callback) {
	var (
		q    amqp.Queue
		ch   *amqp.Channel
		err  error
		msgs <-chan amqp.Delivery
	)

	ch, err = r.GetChannel()

	fmt.Println("CHANNEL", ch, err)

	q, err = ch.QueueDeclare(
		queue.Name,
		queue.Durable,
		queue.AutoDelete,
		queue.Exclusive,
		queue.NoWait,
		queue.Args,
	)

	fmt.Println("QUEUE", q, err)

	if err != nil {
		//TODO
	}

	ch.Qos(1, 0, false)

	msgs, err = ch.Consume(
		q.Name, // queue
		"",     // consumer
		false,  // auto-ack
		false,  // exclusive
		false,  // no-local
		false,  // no-wait
		nil,    // args
	)

	fmt.Println("CONSUME", msgs, err)

	forever := make(chan bool)
	go callback(msgs)
	log.Printf(" [*] Waiting for messages. To exit press CTRL+C")
	<-forever
}

func consumerCallback(msgs <-chan amqp.Delivery) {
	for d := range msgs {
		log.Printf("Received a message: %s", d.Body)
		//dot_count := bytes.Count(d.Body, []byte("."))
		//t := time.Duration(dot_count)
		//time.Sleep(t * time.Second)
		//log.Printf("Done")
		d.Ack(false)
	}
}

func main() {
	//conn, err := amqp.Dial("amqp://guest:guest@localhost:5672/")
	//failOnError(err, "Failed to connect to RabbitMQ")
	//
	//go consumer(conn)

	conn := CreateConnection("localhost", 5672, "guest", "guest")

	fmt.Println(conn)

	//callback := func(msgs <-chan amqp.Delivery) {
	//	for d := range msgs {
	//		log.Printf("Received a message: %s", d.Body)
	//		//dot_count := bytes.Count(d.Body, []byte("."))
	//		//t := time.Duration(dot_count)
	//		//time.Sleep(t * time.Second)
	//		//log.Printf("Done")
	//		d.Ack(false)
	//	}
	//}

	go conn.Consumer(Queue{
		Name:       "task_queue",
		Durable:    true,
		AutoDelete: false,
		Exclusive:  false,
		NoWait:     false,
		Args:       nil,
	}, consumerCallback)

	var input string
	fmt.Scanln(&input)
}

func consumer(conn *amqp.Connection) {

	log.Println("asdasd")

	ch, err := conn.Channel()
	failOnError(err, "Failed to open a channel")
	defer ch.Close()
	defer conn.Close()

	q, err := ch.QueueDeclare(
		"task_queue", // name
		true,         // durable
		false,        // delete when unused
		false,        // exclusive
		false,        // no-wait
		nil,          // arguments
	)
	failOnError(err, "Failed to declare a queue")

	err = ch.Qos(
		1,     // prefetch count
		0,     // prefetch size
		false, // global
	)
	failOnError(err, "Failed to set QoS")

	msgs, err := ch.Consume(
		q.Name, // queue
		"",     // consumer
		false,  // auto-ack
		false,  // exclusive
		false,  // no-local
		false,  // no-wait
		nil,    // args
	)
	failOnError(err, "Failed to register a consumer")

	forever := make(chan bool)

	go func() {
		for d := range msgs {
			log.Printf("Received a message: %s", d.Body)
			dot_count := bytes.Count(d.Body, []byte("."))
			t := time.Duration(dot_count)
			time.Sleep(t * time.Second)
			log.Printf("Done")
			d.Ack(false)
		}
	}()

	log.Printf(" [*] Waiting for messages. To exit press CTRL+C")
	<-forever
}
