package main

import (
	"os"
	"strconv"
	"strings"
	"testing"
	"time"

	"github.com/streadway/amqp"
	"github.com/stretchr/testify/assert"
	"limiter/pkg/env"
	"limiter/pkg/logger"
	"limiter/pkg/rabbitmq"
	"limiter/pkg/storage"
	stringsUtils "limiter/pkg/strings"
	"limiter/pkg/tcp"
)

const outputQueue = "limiter.test_output"

func TestLimiterApp(t *testing.T) {
	stopTest := make(chan bool, 1)
	go timeoutExit(t, stopTest)

	setTestEnv()
	// run app and give it some time to init tcp server
	go main()
	time.Sleep(time.Millisecond * 50)

	// send tcp and amqp requests to limiter
	go simulateTraffic(t, stopTest)

	// wait for stopTest message
	<-stopTest
}

func setTestEnv() {
	os.Setenv("MONGO_HOST", env.GetEnv("MONGO_HOST", "mongodb"))
	os.Setenv("MONGO_DB", "limiter_test")
	os.Setenv("MONGO_COLLECTION", "messages")

	os.Setenv("RABBITMQ_HOST", env.GetEnv("RABBITMQ_HOST", "rabbitmq"))
	os.Setenv("RABBITMQ_PORT", "5672")
	os.Setenv("RABBITMQ_USER", "guest")
	os.Setenv("RABBITMQ_PASS", "guest")
	os.Setenv("RABBITMQ_INPUT_QUEUE", "limiter.test_input")

	os.Setenv("LIMITER_ADDR", "127.0.0.1:3030")
}

func timeoutExit(t *testing.T, stopTest chan bool) {
	time.Sleep(time.Second * 5)
	assert.Fail(t, "Test exceeded max permitted duration limit")
	stopTest <- true
}

func simulateTraffic(t *testing.T, stopTest chan bool) {
	conn, _ := connectRemotes()
	publisher := rabbitmq.NewPublisher(conn, os.Getenv("RABBITMQ_INPUT_QUEUE"), logger.GetNullLogger())

	// max 2 requests per 1 second
	timA := 2
	valA := 2
	// max 5 requests per 2 seconds
	timB := 3
	valB := 5

	// A can have max 2 requests within 1s and we publish 6 messages, so 4 should be postponed (2 by 2s and 2 by 4s)
	// B can have max 5 requests within 2s and we publish 6 messages, so 1 should be postponed by 3s
	for i := 1; i <= 6; i++ {
		isOverLimitA := i > valA
		clientCheckCall(t, publisher, "A", timA, valA, !isOverLimitA)
		isOverLimitB := i > valB
		clientCheckCall(t, publisher, "B", timB, valB, !isOverLimitB)
	}

	consumer := rabbitmq.NewConsumer(conn, outputQueue, logger.GetNullLogger(), 500)
	var rcvdMsgs [5]amqp.Delivery
	rcvdCount := 0
	expectedCount := 5
	go consumer.Consume(func(msgs <-chan amqp.Delivery) {
		i := 0
		for m := range msgs {
			rcvdMsgs[i] = m
			m.Ack(false)
			i++
			rcvdCount++

			if rcvdCount == expectedCount {
				assert.Equal(t, "testA", string(rcvdMsgs[0].Body))
				assert.Equal(t, "testA", string(rcvdMsgs[1].Body))
				assert.Equal(t, "testB", string(rcvdMsgs[2].Body))
				assert.Equal(t, "testA", string(rcvdMsgs[3].Body))
				assert.Equal(t, "testA", string(rcvdMsgs[4].Body))
				stopTest <- true
			}
		}
	})
}

// connectRemotes creates connection to rabbitmq and mongo which are necessary for this integration test
func connectRemotes() (rabbitmq.Connection, *storage.Mongo) {
	rabbitPort, _ := strconv.Atoi(os.Getenv("RABBITMQ_PORT"))
	conn := rabbitmq.NewConnection(os.Getenv("RABBITMQ_HOST"), rabbitPort, os.Getenv("RABBITMQ_USER"), os.Getenv("RABBITMQ_PASS"), logger.GetNullLogger())
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
	m := storage.NewMongo(os.Getenv("MONGO_HOST"), os.Getenv("MONGO_DB"), os.Getenv("MONGO_COLLECTION"), logger.GetNullLogger())
	m.Connect()
	m.DropCollection()

	return conn, m
}

func clientCheckCall(t *testing.T, publisher rabbitmq.Publisher, key string, time int, val int, expected bool) bool {
	limiterHost := os.Getenv("LIMITER_ADDR")

	reqID := stringsUtils.Random(5, true)
	content := tcp.CreateTCPCheckRequestContent(reqID, key, time, val)

	response, err := tcp.SendTCPPacket(limiterHost, content)
	assert.Nil(t, err, "There should be no error when sending tcp check request")

	sl := strings.Split(response, ";")
	last := sl[len(sl)-1]

	if last == "ok" {
		assert.Equal(t, expected, true)
		return true
	}

	assert.Equal(t, expected, false)
	publisher.Publish(newAmqpInputMessage(key, time, val, "test"+key))

	return false
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
	}
}
