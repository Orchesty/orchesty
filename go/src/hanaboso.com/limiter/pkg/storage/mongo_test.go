package storage

import (
	"testing"
	"github.com/stretchr/testify/assert"
	"github.com/streadway/amqp"
)

const (
	mongoHost       = "127.0.0.1"
	mongoDb         = "test"
	mongoCollection = "messages_test"
)

// TestMongoMethods checks implementation of storage interface methods against real mongo instance
func TestMongoMethods(t *testing.T) {
	m := NewMongo(mongoHost, mongoDb, mongoCollection)
	m.Connect()
	m.session.DB("test").C("messages_test").DropCollection()

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
		"pf-limit-key":   "abcd123",
		"pf-limit-time":  "10",
		"pf-limit-value": "10",
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
		"pf-limit-key":   "efgh456",
		"pf-limit-time":  "10",
		"pf-limit-value": "10",
	}})
	m.Save(msgTwo)

	k, err = m.Save(msgOne)
	assert.Nil(t, err)
	assert.Equal(t, "abcd123", k, "Save should allow saving multiple messages with same key")

	fetched, err = m.Get("abcd123", 5)
	assert.Nil(t, err)
	assert.Len(t, fetched, 2, "Get should return all messages if the required length is greater")

	fetched, err = m.Get("abcd123", 1)
	assert.Nil(t, err)
	assert.Len(t, fetched, 1, "Get should return maximally the required length of messages")

	keys, err = m.GetDistinctFirstItems()
	assert.Nil(t, err)
	assert.Len(t, keys, 2, "GetAllKeys should return distinct keys")
	assert.Contains(t, keys, "abcd123")
	assert.Contains(t, keys, "efgh456")
}
