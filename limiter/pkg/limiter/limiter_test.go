package limiter

import (
	"gopkg.in/mgo.v2"

	"limiter/pkg/logger"
	"limiter/pkg/storage"

	"github.com/streadway/amqp"
	"github.com/stretchr/testify/assert"

	"fmt"
	"testing"
	"time"
)

type checkerSaverMock struct{}

func (db *checkerSaverMock) Exists(key string) (bool, error) {
	if key == "when-not-exists" {
		return true, nil
	}
	if key == "on-error" {
		return true, fmt.Errorf("some error")
	}
	return false, nil
}
func (db *checkerSaverMock) CanHandle(key string, time int, value int, groupKey string, groupTime int, groupValue int) (bool, error) {
	return db.Exists(key)
}
func (db *checkerSaverMock) Save(m *storage.Message) (string, error) {
	return "msgKey", nil
}

func (db *checkerSaverMock) CreateIndex(index mgo.Index) error {
	return nil
}

type guardMock struct{}

func (gm *guardMock) IsOnBlacklist(key string) bool {
	return key == "blacklisted"
}
func (gm *guardMock) Check(duration time.Duration) {
	// void
}

// TestIsFreeLimit tests the function using checkerSaver mock object
func TestLimiter_IsFreeLimit(t *testing.T) {
	l := limiter{store: &checkerSaverMock{}, logger: logger.GetNullLogger()}

	res, err := l.IsFreeLimit("when-not-exists", 10, 10, "", 0, 0)
	assert.Nil(t, err)
	assert.True(t, res)

	res, err = l.IsFreeLimit("when-exists", 10, 10, "", 0, 0)
	assert.Nil(t, err)
	assert.False(t, res)

	res, err = l.IsFreeLimit("on-error", 10, 10, "", 0, 0)
	assert.Equal(t, "some error", err.Error())
	assert.False(t, res)
}

func TestLimiter_HandleAmqpMessage_InvalidMessage(t *testing.T) {

	l := limiter{logger: logger.GetNullLogger()}

	msg := amqp.Delivery{
		Body: []byte("message with missing headers"),
	}

	err := l.handleAmqpMessage(msg)
	assert.NotNil(t, err)
	assert.Contains(t, err.Error(), "missing header")
}

func TestLimiter_HandleAmqpMessage_BlacklistedKey(t *testing.T) {

	l := limiter{
		guard:  &guardMock{},
		logger: logger.GetNullLogger(),
	}

	msg := amqp.Delivery{
		Body: []byte("test content"),
		Headers: amqp.Table{
			storage.LimitKeyHeader:         "blacklisted",
			storage.LimitTimeHeader:        "10",
			storage.LimitValueHeader:       "10",
			storage.ReturnExchangeHeader:   "limiter-exchange",
			storage.ReturnRoutingKeyHeader: "limiter-rk",
		},
	}

	err := l.handleAmqpMessage(msg)
	assert.NotNil(t, err)
	assert.Contains(t, err.Error(), "is in blacklist")
}

func TestLimiter_HandleAmqpMessage_OK(t *testing.T) {
	timerChan := make(chan *storage.Message, 1)
	l := limiter{
		guard:     &guardMock{},
		store:     &checkerSaverMock{},
		logger:    logger.GetNullLogger(),
		timerChan: timerChan,
	}

	msg := amqp.Delivery{
		Body: []byte("test content"),
		Headers: amqp.Table{
			storage.LimitKeyHeader:         "someKey",
			storage.LimitTimeHeader:        "10",
			storage.LimitValueHeader:       "10",
			storage.ReturnExchangeHeader:   "limiter-exchange",
			storage.ReturnRoutingKeyHeader: "limiter-rk",
		},
	}

	err := l.handleAmqpMessage(msg)
	assert.Nil(t, err)
	<-timerChan
}
