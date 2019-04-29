package storage

import (
	"limiter/pkg/env"
	"limiter/pkg/logger"

	"github.com/streadway/amqp"
	"github.com/stretchr/testify/assert"

	"os"
	"testing"
	"time"
)

const (
	mongoDb         = "test"
	mongoCollection = "messages_test"
)

// TestMongoMethods checks implementation of storage interface methods against real mongo instance
func TestMongoMethods(t *testing.T) {
	endTestCh := make(chan bool)

	go func(stopMongoTest chan bool) {
		time.Sleep(time.Second * 1)
		stopMongoTest <- false
	}(endTestCh)

	go runTestCommandsInSeries(t, endTestCh)

	// wait for stopTest message
	result := <-endTestCh
	if result == false {
		assert.Fail(t, "Test timeout")
	}
}

func runTestCommandsInSeries(t *testing.T, endTestCh chan bool) {
	os.Setenv("MONGO_HOST", env.GetEnv("MONGO_HOST", "mongodb"))
	mongoHost := os.Getenv("MONGO_HOST")
	m := NewMongo(mongoHost, mongoDb, mongoCollection, logger.GetNullLogger())

	m.Connect()
	m.session.DB("test").C("messages_test").DropCollection()

	count, err := m.Count("abcd123")
	assert.Nil(t, err)
	assert.Equal(t, 0, count)

	keys, err := m.GetDistinctFirstItems()
	assert.Nil(t, err)
	assert.Len(t, keys, 0, "GetAllKeys should return empty slice when collection is empty")

	ex, err := m.Exists("abcd123")
	assert.Nil(t, err)
	assert.False(t, ex, "Exists should return fail when key does not exist")

	fetched, err := m.Get("abcd123", 5)
	assert.Nil(t, err)
	assert.Len(t, fetched, 0, "Get should return empty slice when key does not exist")

	msgOne, _ := NewMessage(&amqp.Delivery{Headers: amqp.Table{
		LimitKeyHeader:         "abcd123",
		LimitTimeHeader:        "10",
		LimitValueHeader:       "500",
		ReturnExchangeHeader:   "exchange",
		ReturnRoutingKeyHeader: "routing-key",
	}})
	k, err := m.Save(msgOne)
	assert.Nil(t, err)
	assert.Equal(t, "abcd123", k, "Save should return the inserted key")

	ex, err = m.Exists("abcd123")
	assert.Nil(t, err)
	assert.True(t, ex, "Exists should return true when key exists")

	fetched, err = m.Get("abcd123", 5)
	assert.Nil(t, err)
	assert.Len(t, fetched, 1, "Get should return one item when there is just one in db")

	msgTwo, _ := NewMessage(&amqp.Delivery{Headers: amqp.Table{
		LimitKeyHeader:         "efgh456",
		LimitTimeHeader:        "10",
		LimitValueHeader:       "500",
		ReturnExchangeHeader:   "exchange",
		ReturnRoutingKeyHeader: "routing-key",
	}})
	m.Save(msgTwo)

	k, err = m.Save(msgOne)
	assert.Nil(t, err)
	assert.Equal(t, "abcd123", k, "Save should allow saving multiple messages with same key")

	count, err = m.Count("abcd123")
	assert.Nil(t, err)
	assert.Equal(t, 2, count)

	count, err = m.Count("efgh456")
	assert.Nil(t, err)
	assert.Equal(t, 1, count)

	fetched, err = m.Get("abcd123", 5)
	assert.Nil(t, err)
	assert.Len(t, fetched, 2, "Get should return all messages if the required length is greater")

	fetched, err = m.Get("abcd123", 1)
	assert.Nil(t, err)
	assert.Len(t, fetched, 1, "Get should return maximally the required length of messages")

	items, err := m.GetDistinctFirstItems()
	assert.Nil(t, err)
	assert.Len(t, items, 2, "GetDistinctFirstItems should return distinct keys and 1 message for each key")
	assert.Equal(t, items["abcd123"].LimitKey, "abcd123")
	assert.Equal(t, items["efgh456"].LimitKey, "efgh456")

	del, err := m.Remove(items["efgh456"].LimitKey, items["efgh456"].ID)
	assert.Nil(t, err)
	assert.True(t, del, "Delete should proceed")

	items, err = m.GetDistinctFirstItems()
	assert.Nil(t, err)
	assert.Len(t, items, 1)

	endTestCh <- true
}

// GetDistinctFirstItems should return the map of messages with distinct keys
// The listed message should be the oldest in storage with the given key
func TestMongo_GetDistinctFirstItems(t *testing.T) {
	os.Setenv("MONGO_HOST", env.GetEnv("MONGO_HOST", "mongodb"))
	mongoHost := os.Getenv("MONGO_HOST")
	mongo := NewMongo(mongoHost, mongoDb, mongoCollection, logger.GetNullLogger())
	mongo.Connect()
	mongo.session.DB("test").C("messages_test").DropCollection()

	items := make(map[string]*Message)
	items["new"] = &Message{
		LimitKey:       "someKey",
		Created:        time.Now(),
		ReturnExchange: "most recent",
	}
	items["fresh"] = &Message{
		LimitKey:       "someKey",
		Created:        time.Now().Add(-time.Minute * 5),
		ReturnExchange: "in the middle",
	}
	items["rotten"] = &Message{
		LimitKey:       "someKey",
		Created:        time.Now().Add(-time.Hour * 25),
		ReturnExchange: "oldest",
	}

	for _, i := range items {
		mongo.Save(i)
	}

	found, _ := mongo.GetDistinctFirstItems()
	rotten, ok := found["someKey"]
	assert.True(t, ok)
	assert.Equal(t, "oldest", rotten.ReturnExchange)
}
