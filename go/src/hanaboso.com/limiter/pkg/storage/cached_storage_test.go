package storage

import (
	"testing"
	"gopkg.in/mgo.v2/bson"
	"github.com/streadway/amqp"
	"github.com/stretchr/testify/assert"
)

type mongoMock struct {}

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
	if key == "not-in-db" {
		return 0, nil
	}
	return 2, nil
}
func (mm *mongoMock) GetDistinctFirstItems() (map[string]*Message, error) {
	return make(map[string]*Message, 0), nil
}

func TestCachedMongoCountingWhenNotPreviouslyInDb(t *testing.T) {
	s := NewCachedMongo(&mongoMock{})

	ex, _ := s.Exists("not-in-db")
	assert.False(t, ex)
	msgA, _ := NewMessage(&amqp.Delivery{Headers: amqp.Table{
		"pf-limit-key":   "not-in-db",
		"pf-limit-time":  "10",
		"pf-limit-value": "10",
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
	s := NewCachedMongo(&mongoMock{})

	ex, _ := s.Exists("was-in-db")
	assert.True(t, ex)
	msgA, _ := NewMessage(&amqp.Delivery{Headers: amqp.Table{
		"pf-limit-key":   "was-in-db",
		"pf-limit-time":  "10",
		"pf-limit-value": "10",
	}})
	s.Save(msgA)
	ex, _ = s.Exists("was-in-db")
	assert.True(t, ex)

	num, _ := s.Count("was-in-db")
	assert.Equal(t, 3, num)

	s.Save(msgA)
	s.Save(msgA)

	num, _ = s.Count("was-in-db")
	assert.Equal(t, 5, num)

	ex, _ = s.Exists("was-in-db")
	assert.True(t, ex)

	s.Remove("was-in-db", bson.NewObjectId())
	s.Remove("was-in-db", bson.NewObjectId())
	s.Remove("was-in-db", bson.NewObjectId())
	s.Remove("was-in-db", bson.NewObjectId())

	num, _ = s.Count("was-in-db")
	assert.Equal(t, 1, num)
}