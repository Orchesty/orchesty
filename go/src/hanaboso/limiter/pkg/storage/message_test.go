package storage

import (
	"github.com/streadway/amqp"
	"github.com/stretchr/testify/assert"
	"testing"
)

// TestNewMessageWithSomeMissingHeaders checks returning error on missing mandatory header
func TestNewMessageWithSomeMissingHeaders(t *testing.T) {
	msg, err := NewMessage(&amqp.Delivery{Headers: amqp.Table{
		LimitTimeHeader:        "10",
		LimitValueHeader:       "10",
		ReturnExchangeHeader:   "exchange",
		ReturnRoutingKeyHeader: "routing-key",
	}})
	assert.Nil(t, msg)
	assert.NotNil(t, err)
	assert.Equal(t, "missing header pf-limit-key", err.Error())

	msg, err = NewMessage(&amqp.Delivery{Headers: amqp.Table{
		LimitKeyHeader:         "#123",
		LimitTimeHeader:        "10",
		ReturnExchangeHeader:   "exchange",
		ReturnRoutingKeyHeader: "routing-key",
	}})
	assert.Nil(t, msg)
	assert.NotNil(t, err)
	assert.Equal(t, "missing header pf-limit-value", err.Error())

	msg, err = NewMessage(&amqp.Delivery{Headers: amqp.Table{
		LimitKeyHeader:         "#123",
		LimitValueHeader:       "10",
		ReturnExchangeHeader:   "exchange",
		ReturnRoutingKeyHeader: "routing-key",
	}})
	assert.Nil(t, msg)
	assert.NotNil(t, err)
	assert.Equal(t, "missing header pf-limit-time", err.Error())

	msg, err = NewMessage(&amqp.Delivery{Headers: amqp.Table{
		LimitKeyHeader:         "#123",
		LimitValueHeader:       "10",
		LimitTimeHeader:        "10",
		ReturnRoutingKeyHeader: "routing-key",
	}})
	assert.Nil(t, msg)
	assert.NotNil(t, err)
	assert.Equal(t, "missing or empty header pf-limit-return-exchange", err.Error())

	msg, err = NewMessage(&amqp.Delivery{Headers: amqp.Table{
		LimitKeyHeader:       "#123",
		LimitValueHeader:     "10",
		LimitTimeHeader:      "10",
		ReturnExchangeHeader: "exchange",
	}})
	assert.Nil(t, msg)
	assert.NotNil(t, err)
	assert.Equal(t, "missing or empty header pf-limit-return-routing-key", err.Error())
}

// TestNewMessageOK checks if Message struct is properly filled with data
func TestNewMessageOK(t *testing.T) {
	msg, err := NewMessage(&amqp.Delivery{Headers: amqp.Table{
		LimitKeyHeader:         "abcd123",
		LimitTimeHeader:        "10",
		LimitValueHeader:       "500",
		ReturnExchangeHeader:   "exchange",
		ReturnRoutingKeyHeader: "routing-key",
	}, Body: []byte("Some content")})
	assert.NotNil(t, msg)
	assert.Nil(t, err)

	assert.Equal(t, "abcd123", msg.LimitKey)
	assert.Equal(t, 10, msg.LimitTime)
	assert.Equal(t, 500, msg.LimitValue)
	assert.Equal(t, string(msg.Message.Body), "Some content")
}
