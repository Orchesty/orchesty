package storage

import (
	"testing"
	"gopkg.in/mgo.v2/bson"
	"github.com/streadway/amqp"
	"github.com/stretchr/testify/assert"
	"time"
)

type mongoMock struct{}

func (mm *mongoMock) CanHandle(key string, time int, value int) (bool, error) {
	return mm.Exists(key)
}

func (mm *mongoMock) Exists(key string) (bool, error) {
	if key == "not-in-db" {
		return false, nil
	}
	return true, nil
}

func (mm *mongoMock) Save(m *Message) (string, error) {
	return m.LimitKey, nil
}
func (mm *mongoMock) Remove(key string, id bson.ObjectId) (bool, error) {
	return true, nil
}

func (mm *mongoMock) Get(key string, length int) ([]*Message, error) {
	return make([]*Message, 0), nil
}
func (mm *mongoMock) Count(key string) (int, error) {
	if key == "already-in-db" {
		return 2, nil
	}
	return 0, nil
}
func (mm *mongoMock) GetDistinctFirstItems() (map[string]*Message, error) {
	return make(map[string]*Message, 0), nil
}

func TestPredictiveCachedStorageMongoEmptyDb(t *testing.T) {
	s := NewPredictiveCachedStorage(&mongoMock{})

	msg, _ := NewMessage(&amqp.Delivery{Headers: amqp.Table{
		LimitKeyHeader:         "not-in-db",
		LimitTimeHeader:        "1",
		LimitValueHeader:       "2",
		ReturnExchangeHeader:   "exchange",
		ReturnRoutingKeyHeader: "routing-key",
	}})

	can, _ := s.CanHandle("not-in-db", 1, 2)
	assert.True(t, can)
	s.Save(msg)

	can, _ = s.CanHandle("not-in-db", 1, 2)
	assert.True(t, can)
	s.Save(msg)

	can, _ = s.CanHandle("not-in-db", 1, 2)
	assert.False(t, can) // now this should be false
	s.Save(msg)

	s.Remove("not-in-db", bson.NewObjectId())
	s.Remove("not-in-db", bson.NewObjectId())
	s.Remove("not-in-db", bson.NewObjectId())

	can, _ = s.CanHandle("not-in-db", 1, 2)
	assert.True(t, can)

	can, _ = s.CanHandle("not-in-db", 1, 2)
	assert.True(t, can)

	can, _ = s.CanHandle("not-in-db", 1, 2)
	assert.False(t, can)
}

func TestPredictiveCachedStorageMongoNonEmptyDb(t *testing.T) {
	s := NewPredictiveCachedStorage(&mongoMock{})

	can, _ := s.CanHandle("already-in-db", 1, 2)
	assert.False(t, can)

	s.Remove("already-in-db", bson.NewObjectId())
	s.Remove("already-in-db", bson.NewObjectId())

	can, _ = s.CanHandle("already-in-db", 1, 2)
	assert.True(t, can)
}

func TestPredictiveCacheStorageItemTicker(t *testing.T) {
	s := NewPredictiveCachedStorage(&mongoMock{})

	can, _ := s.CanHandle("key-A", 1, 2)
	assert.True(t, can)

	can, _ = s.CanHandle("key-A", 1, 2)
	assert.True(t, can)

	can, _ = s.CanHandle("key-A", 1, 2)
	assert.False(t, can) // this one is over limit

	time.Sleep(time.Millisecond * 1050)

	can, _ = s.CanHandle("key-A", 1, 2)
	assert.True(t, can)

	can, _ = s.CanHandle("key-A", 1, 2)
	assert.False(t, can) // this one is over limit

	can, _ = s.CanHandle("key-A", 1, 2)
	assert.False(t, can) // this one is over limit too

	can, _ = s.CanHandle("key-A", 1, 2)
	assert.False(t, can) // this one is over limit as well

	time.Sleep(time.Millisecond * 3050)

	can, _ = s.CanHandle("key-A", 1, 2)
	assert.True(t, can)

	num, _ := s.Count("key-a")
	assert.Equal(t, 0, num)
}

func TestPredictiveCachedStorageMixingTickersWithSaveAndRemove(t *testing.T) {
	assert.Fail(t, "TODO - test while mixing tickers with save/remove methods")
}
