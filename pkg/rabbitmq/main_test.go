package rabbitmq

import (
	"github.com/streadway/amqp"
	"os"
	"testing"
)

type testingClient struct {
	client
	ch *amqp.Channel
}

func (tc *testingClient) connect() {
	ch, err := tc.connection.Channel()
	if err != nil {
		panic(err)
	}
	tc.ch = ch
}

func (tc *testingClient) close() {
	_ = tc.connection.Close()
}

var tClient *testingClient

func setupTestData() {
	tClient = &testingClient{}
	go tClient.handleReconnect(tClient, "amqp://rabbitmq")
}

func teardown() {
	tClient.close()
}

func TestMain(m *testing.M) {
	setupTestData()
	code := m.Run()
	teardown()
	os.Exit(code)
}
