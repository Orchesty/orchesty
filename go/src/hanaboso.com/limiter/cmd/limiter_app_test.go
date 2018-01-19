package main

import (
	"testing"
	"os"
	"time"
	"github.com/stretchr/testify/assert"
	stringsUtils "hanaboso.com/utils/strings"
	"strconv"
	"strings"
	"hanaboso.com/limiter/pkg/rabbitmq"
	"github.com/streadway/amqp"
	"hanaboso.com/limiter/pkg/storage"
	"hanaboso.com/utils/env"
	"hanaboso.com/limiter/pkg/limiter"
)

const outputQueue = "limiter.test_output"

func TestLimiterApp(t *testing.T) {
	stopTest := make(chan bool, 1)
	go timeoutExit(t, stopTest)

	setTestEnv()
	// run app and give it some time to init
	go main()
	time.Sleep(time.Millisecond * 100)

	// send tcp and amqp requests to limiter
	go simulateTraffic(t, stopTest)

	// wait for stopTest message
	<-stopTest
}

func setTestEnv() {
	os.Setenv("MONGO_HOST", env.GetEnv("MONGO_HOST", "localhost"))
	os.Setenv("MONGO_DB", "limiter_test")
	os.Setenv("MONGO_COLLECTION", "messages")

	os.Setenv("RABBITMQ_HOST", env.GetEnv("RABBITMQ_HOST", "localhost"))
	os.Setenv("RABBITMQ_PORT", "5672")
	os.Setenv("RABBITMQ_USER", "guest")
	os.Setenv("RABBITMQ_PASS", "guest")
	os.Setenv("RABBITMQ_INPUT_QUEUE", "limiter.test_input")

	os.Setenv("LIMITER_PORT", "3030")
}

func timeoutExit(t *testing.T, stopTest chan bool) {
	time.Sleep(time.Second * 500)
	assert.Fail(t, "Test exceeded max permitted duration limit")
	stopTest <- true
}

func simulateTraffic(t *testing.T, stopTest chan bool) {
	conn, _ := connectRemotes()
	publisher := rabbitmq.NewPublisher(conn, os.Getenv("RABBITMQ_INPUT_QUEUE"))

	go tcpCheck(t, "A", 1, 2, true)
	go tcpCheck(t, "B", 2, 5, true)

	// A can have max 2 requests within 1s and we publish 3 so it should wait and tick there for 2s
	msgsSent := 3
	for i := 1; i <= msgsSent; i++ {
		publisher.Publish(newAmqpInputMessage("A", 1, 2, "test"+strconv.Itoa(i)))
	}

	// give limiter some time to handle incoming messages
	time.Sleep(time.Millisecond * 50)

	// now we should be notified about existing limit for A, but B should not be affected by A's limit
	go tcpCheck(t, "A", 1, 2, false) // here should be false now
	go tcpCheck(t, "B", 2, 50, true)

	//// after limit should be free again we should get positive responses
	time.Sleep(time.Second * 2)
	go tcpCheck(t, "A", 1, 2, true)
	go tcpCheck(t, "B", 2, 50, true)

	consumer := rabbitmq.NewConsumer(conn, outputQueue)
	msgsReceived := 0
	go consumer.Consume(func(msgs <-chan amqp.Delivery) {
		for m := range msgs {
			msgsReceived++
			m.Ack(false)

			assert.Equal(t, "test"+strconv.Itoa(msgsReceived), string(m.Body), "Messages on the output should be properly FIFO sorted")
			assert.Equal(t, "A", m.Headers["pf-limit-key"])

			if msgsReceived == msgsSent {
				stopTest <- true
			}
		}
	})
}

// connectRemotes creates connection to rabbitmq and mongo which are necessary for this integration test
func connectRemotes() (rabbitmq.Connection, *storage.Mongo) {
	rabbitPort, _ := strconv.Atoi(os.Getenv("RABBITMQ_PORT"))
	conn := rabbitmq.NewConnection(os.Getenv("RABBITMQ_HOST"), rabbitPort, os.Getenv("RABBITMQ_USER"), os.Getenv("RABBITMQ_PASS"))
	conn.Connect()

	inQueue := rabbitmq.Queue{Name: os.Getenv("RABBITMQ_INPUT_QUEUE")}
	conn.PurgeQueue(inQueue)

	outQueue := rabbitmq.Queue{Name: outputQueue}
	outQueue.AddBinding(rabbitmq.Binding{Exchange: "limiter-exchange", RoutingKey: outputQueue})
	conn.AddQueue(outQueue)
	conn.AddExchange(rabbitmq.Exchange{Name: "limiter-exchange", Type: "direct"})
	conn.PurgeQueue(outQueue)

	conn.Setup()

	// Clean database before each test
	m := storage.NewMongo(os.Getenv("MONGO_HOST"), os.Getenv("MONGO_DB"), os.Getenv("MONGO_COLLECTION"))
	m.Connect()
	m.DropCollection()

	return conn, m
}

func tcpCheck(t *testing.T, key string, time int, val int, expected bool) {
	limiterHost := "localhost:"+os.Getenv("LIMITER_PORT")

	reqID := stringsUtils.Random(5, true)
	content := limiter.CreateTcpCheckRequestContent(reqID, key, time, val)

	response, err := limiter.SendTcpPacket(limiterHost, content)
	assert.Nil(t, err, "There should be no error when sending tcp check request")

	sl := strings.Split(response, ";")
	last := sl[len(sl)-1]

	if expected {
		assert.Equal(t, "ok", last)
	} else {
		assert.Equal(t, "nok", last)
	}
}

func newAmqpInputMessage(key string, time int, value int, body string) amqp.Publishing {
	return amqp.Publishing{
		Body: []byte(body),
		Headers: amqp.Table{
			storage.LimitKeyHeader:         key,
			storage.LimitTimeHeader:        strconv.Itoa(time),
			storage.LimitValueHeader:       strconv.Itoa(value),
			storage.ReturnExchangeHeader:   "limiter-exchange",
			storage.ReturnRoutingKeyHeader: outputQueue,
		},
		ReplyTo: outputQueue,
	}
}
