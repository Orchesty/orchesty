package storage

import (
	"encoding/json"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"limiter/pkg/logger"

	"github.com/streadway/amqp"
	"github.com/stretchr/testify/assert"

	"testing"
	"time"
)

type mongoMock struct{}

func (mm *mongoMock) ClearCacheItem(key string, val int) bool {
	return true
}

func (mm *mongoMock) CanHandle(key string, time int, value int, groupKey string, groupTime int, groupValue int) (bool, error) {
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
func (mm *mongoMock) Remove(key string, id primitive.ObjectID) (bool, error) {
	return true, nil
}

func (mm *mongoMock) Get(key string, length int) ([]*Message, error) {
	return make([]*Message, 0), nil
}
func (mm *mongoMock) GetMessages(field, key string, length int) ([]*Message, error) {
	return make([]*Message, 0), nil
}
func (mm *mongoMock) Count(key string, limit int) (int, error) {
	if key == "already-in-db" {
		return 2, nil
	}
	return 0, nil
}

func (mm *mongoMock) CountInGroup(keys []string, limit int) (int, error) {
	return 0, nil
}

func (mm *mongoMock) GetDistinctFirstItems() (map[string]*Message, error) {
	return make(map[string]*Message, 0), nil
}

func (mm *mongoMock) GetDistinctGroupFirstItems() (map[string]*Message, error) {
	return make(map[string]*Message, 0), nil
}

func (mm *mongoMock) CreateIndex(index mongo.IndexModel) error {
	return nil
}

// TestPredictiveCachedStorage_MongoEmptyDb tests keeping the cache items and it's tickers
// calling save() and remove() should not influence the cached tickers
func TestPredictiveCachedStorage_MongoEmptyDb(t *testing.T) {
	s := NewPredictiveCachedStorage(&mongoMock{}, time.Hour*time.Duration(24), logger.GetNullLogger())

	jsonData, _ := json.Marshal(map[string]interface{}{
		"body": "Some content",
		"headers": map[string]string{
			LimitKeyHeader:         "not-in-db",
			LimitTimeHeader:        "1",
			LimitValueHeader:       "2",
			ReturnExchangeHeader:   "exchange",
			ReturnRoutingKeyHeader: "routing-key",
		},
	})

	msg, _ := NewMessage(&amqp.Delivery{Body: jsonData})

	assert.False(t, s.hasCachedItem("not-in-db"))

	can, _ := s.CanHandle("not-in-db", 1, 2, "", 0, 0)
	assert.True(t, can)
	s.Save(msg)

	can, _ = s.CanHandle("not-in-db", 1, 2, "", 0, 0)
	assert.True(t, can)
	s.Save(msg)

	can, _ = s.CanHandle("not-in-db", 1, 2, "", 0, 0)
	assert.False(t, can) // now this should be false
	s.Save(msg)

	assert.True(t, s.hasCachedItem("not-in-db"))

	s.Remove("not-in-db", primitive.NewObjectID())
	s.Remove("not-in-db", primitive.NewObjectID())
	s.Remove("not-in-db", primitive.NewObjectID())

	assert.True(t, s.hasCachedItem("not-in-db"))

	can, _ = s.CanHandle("not-in-db", 1, 2, "", 0, 0)
	assert.False(t, can)

	can, _ = s.CanHandle("not-in-db", 1, 2, "", 0, 0)
	assert.False(t, can)

	can, _ = s.CanHandle("not-in-db", 1, 2, "", 0, 0)
	assert.False(t, can)

	assert.True(t, s.hasCachedItem("not-in-db"))

	// In 3s limit should be free again
	//time.Sleep(time.Millisecond * 3050)

	assert.True(t, s.hasCachedItem("not-in-db"))

	can, _ = s.CanHandle("not-in-db", 1, 2, "", 0, 0)
	assert.False(t, can)
	assert.True(t, s.hasCachedItem("not-in-db"))
}

func TestPredictiveCachedStorage_MongoNonEmptyDb(t *testing.T) {
	s := NewPredictiveCachedStorage(&mongoMock{}, time.Hour*24, logger.GetNullLogger())

	assert.False(t, s.hasCachedItem("already-in-db"))

	can, _ := s.CanHandle("already-in-db", 1, 2, "", 0, 0)
	//TODO: add test for new behaviour - not check in mongo here
	assert.True(t, can)
	assert.True(t, s.hasCachedItem("already-in-db"))

	// remove does not impact the cache
	s.Remove("already-in-db", primitive.NewObjectID())
	s.Remove("already-in-db", primitive.NewObjectID())

	assert.True(t, s.hasCachedItem("already-in-db"))

	can, _ = s.CanHandle("already-in-db", 1, 2, "", 0, 0)
	//TODO: add test for new behaviour - not check in mongo here
	assert.True(t, can)

	can, _ = s.CanHandle("already-in-db", 1, 2, "", 0, 0)
	assert.False(t, can)

	assert.True(t, s.hasCachedItem("already-in-db"))

	// In 3s limit should be free again
	time.Sleep(time.Millisecond * 3050)

	assert.False(t, s.hasCachedItem("already-in-db"))
}

func TestPredictiveCacheStorage_ItemTicker(t *testing.T) {
	s := NewPredictiveCachedStorage(&mongoMock{}, time.Hour*24, logger.GetNullLogger())

	can, _ := s.CanHandle("key-A", 1, 2, "", 0, 0)
	assert.True(t, can)

	can, _ = s.CanHandle("key-A", 1, 2, "", 0, 0)
	assert.True(t, can)

	can, _ = s.CanHandle("key-A", 1, 2, "", 0, 0)
	assert.False(t, can) // this one is over limit

	time.Sleep(time.Millisecond * 1050)

	can, _ = s.CanHandle("key-A", 1, 2, "", 0, 0)
	//TODO: add test for new behaviour - not check in mongo here
	assert.True(t, can)

	can, _ = s.CanHandle("key-A", 1, 2, "", 0, 0)
	assert.True(t, can) // this one is over limit

	can, _ = s.CanHandle("key-A", 1, 2, "", 0, 0)
	assert.False(t, can) // this one is over limit too

	can, _ = s.CanHandle("key-A", 1, 2, "", 0, 0)
	assert.False(t, can) // this one is over limit as well

	time.Sleep(time.Millisecond * 3050)

	can, _ = s.CanHandle("key-A", 1, 2, "", 0, 0)
	assert.True(t, can)

	num, _ := s.Count("key-a", 1)
	assert.Equal(t, 0, num)
}

func TestPredictiveCachedStorage_AutoClean(t *testing.T) {
	s := NewPredictiveCachedStorage(&mongoMock{}, time.Second, logger.GetNullLogger())

	assert.False(t, s.hasCachedItem("not-in-db"))
	assert.False(t, s.hasCachedItem("already-in-db"))

	s.CanHandle("not-in-db", 10, 2, "", 0, 0)
	s.CanHandle("already-in-db", 10, 2, "", 0, 0)

	assert.True(t, s.hasCachedItem("not-in-db"))
	assert.True(t, s.hasCachedItem("already-in-db"))

	time.Sleep(time.Millisecond * 1050)

	assert.False(t, s.hasCachedItem("not-in-db"))
	assert.False(t, s.hasCachedItem("already-in-db"))

	can, _ := s.CanHandle("not-in-db", 10, 2, "", 0, 0)
	assert.True(t, can)

	// This key is found in db and even though the cache item does not exist CanHandle() should return false
	can, _ = s.CanHandle("already-in-db", 10, 2, "", 0, 0)
	//TODO: add test for new behaviour - not check in mongo here
	assert.True(t, can)
}
