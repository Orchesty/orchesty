package storage

import (
	"github.com/streadway/amqp"
	"github.com/stretchr/testify/assert"
	"gopkg.in/mgo.v2"
	"gopkg.in/mgo.v2/bson"
	"testing"
)

type csMongoMock struct{}

func (mm *csMongoMock) ClearCacheItem(key string, val int) bool {
	return true
}

func (mm *csMongoMock) CanHandle(key string, interval int, value int, groupKey string, groupTime int, groupValue int) (bool, error) {
	has, _ := mm.Exists(key)

	return !has, nil
}

func (mm *csMongoMock) Exists(key string) (bool, error) {
	if key == "not-in-db" {
		return false, nil
	}
	return true, nil
}

func (mm *csMongoMock) Save(m *Message) (string, error) {
	return m.LimitKey, nil
}
func (mm *csMongoMock) Remove(key string, id bson.ObjectId) (bool, error) {
	return true, nil
}

func (mm *csMongoMock) Get(key string, length int) ([]*Message, error) {
	return make([]*Message, 0), nil
}

func (mm *csMongoMock) GetMessages(field, key string, length int) ([]*Message, error) {
	return make([]*Message, 0), nil
}

func (mm *csMongoMock) Count(key string, limit int) (int, error) {
	if key == "not-in-db" {
		return 0, nil
	}
	return 2, nil
}
func (mm *csMongoMock) CountInGroup(keys []string, limit int) (int, error) {
	panic("not implemented")
}

func (mm *csMongoMock) GetDistinctFirstItems() (map[string]*Message, error) {
	return make(map[string]*Message, 0), nil
}

func (mm *csMongoMock) GetDistinctGroupFirstItems() (map[string]*Message, error) {
	return make(map[string]*Message, 0), nil
}

func (mm *csMongoMock) CreateIndex(index mgo.Index) error {
	return nil
}

func TestCachedMongoCountingWhenNotPreviouslyInDb(t *testing.T) {
	s := NewCachedMongo(&csMongoMock{})

	ex, _ := s.Exists("not-in-db")
	assert.False(t, ex)
	msgA, _ := NewMessage(&amqp.Delivery{Headers: amqp.Table{
		LimitKeyHeader:         "not-in-db",
		LimitTimeHeader:        "10",
		LimitValueHeader:       "10",
		ReturnExchangeHeader:   "exchange",
		ReturnRoutingKeyHeader: "routing-key",
	}})
	s.Save(msgA)
	ex, _ = s.Exists("not-in-db")
	assert.True(t, ex)

	s.Save(msgA)
	s.Save(msgA)

	ex, _ = s.Exists("not-in-db")
	assert.True(t, ex)

	s.Remove("not-in-db", bson.NewObjectId())
	s.Remove("not-in-db", bson.NewObjectId())
	s.Remove("not-in-db", bson.NewObjectId())

	ex, _ = s.Exists("not-in-db")
	assert.False(t, ex)
}

func TestCachedMongoCountingWhenAlreadyInDb(t *testing.T) {
	s := NewCachedMongo(&csMongoMock{})

	ex, _ := s.Exists("was-in-db")
	assert.True(t, ex)
	msgA, _ := NewMessage(&amqp.Delivery{Headers: amqp.Table{
		LimitKeyHeader:         "was-in-db",
		LimitTimeHeader:        "10",
		LimitValueHeader:       "10",
		ReturnExchangeHeader:   "exchange",
		ReturnRoutingKeyHeader: "routing-key",
	}})
	s.Save(msgA)
	ex, _ = s.Exists("was-in-db")
	assert.True(t, ex)

	num, _ := s.Count("was-in-db", 3)
	assert.Equal(t, 3, num)

	s.Save(msgA)
	s.Save(msgA)

	num, _ = s.Count("was-in-db", 5)
	assert.Equal(t, 5, num)

	ex, _ = s.Exists("was-in-db")
	assert.True(t, ex)

	s.Remove("was-in-db", bson.NewObjectId())
	s.Remove("was-in-db", bson.NewObjectId())
	s.Remove("was-in-db", bson.NewObjectId())
	s.Remove("was-in-db", bson.NewObjectId())

	num, _ = s.Count("was-in-db", 1)
	assert.Equal(t, 1, num)
}
