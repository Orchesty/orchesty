package storage

import (
	"testing"
	"github.com/streadway/amqp"
	"github.com/stretchr/testify/assert"
)

// TestNewMessageWithSomeMissingHeaders checks returning error on missing mandatory header
func TestNewMessageWithSomeMissingHeaders(t *testing.T) {
	msg, err := NewMessage(&amqp.Delivery{Headers: amqp.Table{
		"pf-limit-time":  "10",
		"pf-limit-value": "10",
	}})
	assert.Nil(t, msg)
	assert.NotNil(t, err)

	msg, err = NewMessage(&amqp.Delivery{Headers: amqp.Table{
		"pf-limit-key":   "abcd123",
		"pf-limit-time":  "10",
	}})
	assert.Nil(t, msg)
	assert.NotNil(t, err)

	msg, err = NewMessage(&amqp.Delivery{Headers: amqp.Table{
		"pf-limit-key":   "abcd123",
		"pf-limit-value": "10",
	}})
	assert.Nil(t, msg)
	assert.NotNil(t, err)
}

// TestNewMessageOK checks if Message struct is properly filled with data
func TestNewMessageOK(t *testing.T) {
	msg, err := NewMessage(&amqp.Delivery{Headers: amqp.Table{
		"pf-limit-key":   "abcd123",
		"pf-limit-time":  "10",
		"pf-limit-value": "500",
	}, Body: []byte("Some content")})
	assert.NotNil(t, msg)
	assert.Nil(t, err)

	assert.Equal(t, "abcd123", msg.LimitKey)
	assert.Equal(t, 10, msg.LimitTime)
	assert.Equal(t, 500, msg.LimitValue)
	assert.Equal(t, string(msg.Message.Body), "Some content")
}
