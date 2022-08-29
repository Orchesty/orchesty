package storage

import (
	"encoding/json"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
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
func (mm *csMongoMock) Remove(key string, id primitive.ObjectID) (bool, error) {
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

func (mm *csMongoMock) CreateIndex(index mongo.IndexModel) error {
	return nil
}

func TestCachedMongoCountingWhenNotPreviouslyInDb(t *testing.T) {
	s := NewCachedMongo(&csMongoMock{})

	ex, _ := s.Exists("not-in-db")
	assert.False(t, ex)

	jsonData, _ := json.Marshal(map[string]interface{}{
		"body": "test content",
		"headers": map[string]interface{}{
			LimitKeyHeader:         "not-in-db",
			LimitTimeHeader:        "10",
			LimitValueHeader:       "10",
			ReturnExchangeHeader:   "exchange",
			ReturnRoutingKeyHeader: "routing-key",
		},
	})

	msgA, _ := NewMessage(&amqp.Delivery{Body: jsonData})
	s.Save(msgA)
	ex, _ = s.Exists("not-in-db")
	assert.True(t, ex)

	s.Save(msgA)
	s.Save(msgA)

	ex, _ = s.Exists("not-in-db")
	assert.True(t, ex)

	s.Remove("not-in-db", primitive.NewObjectID())
	s.Remove("not-in-db", primitive.NewObjectID())
	s.Remove("not-in-db", primitive.NewObjectID())

	ex, _ = s.Exists("not-in-db")
	assert.False(t, ex)
}

func TestCachedMongoCountingWhenAlreadyInDb(t *testing.T) {
	s := NewCachedMongo(&csMongoMock{})

	ex, _ := s.Exists("was-in-db")
	assert.True(t, ex)

	jsonData, _ := json.Marshal(map[string]interface{}{
		"body": "test content",
		"headers": map[string]interface{}{
			LimitKeyHeader:         "was-in-db",
			LimitTimeHeader:        "10",
			LimitValueHeader:       "10",
			ReturnExchangeHeader:   "exchange",
			ReturnRoutingKeyHeader: "routing-key",
		},
	})
	msgA, _ := NewMessage(&amqp.Delivery{Body: jsonData})
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

	s.Remove("was-in-db", primitive.NewObjectID())
	s.Remove("was-in-db", primitive.NewObjectID())
	s.Remove("was-in-db", primitive.NewObjectID())
	s.Remove("was-in-db", primitive.NewObjectID())

	num, _ = s.Count("was-in-db", 1)
	assert.Equal(t, 1, num)
}
