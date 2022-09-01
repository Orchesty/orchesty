package storage

import (
	"encoding/json"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/stretchr/testify/assert"
	"limiter/pkg/model"
	"testing"
)

// TestNewMessageWithSomeMissingHeaders checks returning error on missing mandatory header
func TestNewMessageWithSomeMissingHeaders(t *testing.T) {
	jsonData, _ := json.Marshal(map[string]interface{}{
		"body": "test content",
		"headers": map[string]string{
			LimitTimeHeader:        "10",
			LimitValueHeader:       "10",
			ReturnExchangeHeader:   "exchange",
			ReturnRoutingKeyHeader: "routing-key",
		},
	})

	msg, err := NewMessage(&amqp.Delivery{Body: jsonData})
	assert.Nil(t, msg)
	assert.NotNil(t, err)
	assert.Equal(t, "missing header limit-key", err.Error())

	jsonData, _ = json.Marshal(map[string]interface{}{
		"body": "test content",
		"headers": map[string]string{
			LimitKeyHeader:         "#123",
			LimitTimeHeader:        "10",
			ReturnExchangeHeader:   "exchange",
			ReturnRoutingKeyHeader: "routing-key",
		},
	})

	msg, err = NewMessage(&amqp.Delivery{Body: jsonData})
	assert.Nil(t, msg)
	assert.NotNil(t, err)
	assert.Equal(t, "missing header limit-value", err.Error())

	jsonData, _ = json.Marshal(map[string]interface{}{
		"body": "test content",
		"headers": map[string]string{
			LimitKeyHeader:         "#123",
			LimitValueHeader:       "10",
			ReturnExchangeHeader:   "exchange",
			ReturnRoutingKeyHeader: "routing-key",
		},
	})

	msg, err = NewMessage(&amqp.Delivery{Body: jsonData})
	assert.Nil(t, msg)
	assert.NotNil(t, err)
	assert.Equal(t, "missing header limit-time", err.Error())

	jsonData, _ = json.Marshal(map[string]interface{}{
		"body": "test content",
		"headers": map[string]string{
			LimitKeyHeader:         "#123",
			LimitValueHeader:       "10",
			LimitTimeHeader:        "10",
			ReturnRoutingKeyHeader: "routing-key",
		},
	})

	msg, err = NewMessage(&amqp.Delivery{Body: jsonData})
	assert.Nil(t, msg)
	assert.NotNil(t, err)
	assert.Equal(t, "missing or empty header limit-return-exchange", err.Error())

	jsonData, _ = json.Marshal(map[string]interface{}{
		"body": "test content",
		"headers": map[string]string{
			LimitKeyHeader:       "#123",
			LimitValueHeader:     "10",
			LimitTimeHeader:      "10",
			ReturnExchangeHeader: "exchange",
		},
	})

	msg, err = NewMessage(&amqp.Delivery{Body: jsonData})
	assert.Nil(t, msg)
	assert.NotNil(t, err)
	assert.Equal(t, "missing or empty header limit-return-routing-key", err.Error())
}

// TestNewMessageOK checks if Message struct is properly filled with data
func TestNewMessageOK(t *testing.T) {
	jsonData, _ := json.Marshal(map[string]interface{}{
		"body": "Some content",
		"headers": map[string]string{
			LimitKeyHeader:         "abcd123",
			LimitTimeHeader:        "10",
			LimitValueHeader:       "500",
			ReturnExchangeHeader:   "exchange",
			ReturnRoutingKeyHeader: "routing-key",
		},
	})

	msg, err := NewMessage(&amqp.Delivery{Body: jsonData})
	assert.NotNil(t, msg)
	assert.Nil(t, err)

	var processDto model.ProcessDto
	processDtoErr := json.Unmarshal(msg.Message.Body, &processDto)
	if processDtoErr != nil {
		return
	}

	assert.Equal(t, "abcd123", msg.LimitKey)
	assert.Equal(t, 10, msg.LimitTime)
	assert.Equal(t, 500, msg.LimitValue)
	assert.Equal(t, processDto.Body, "Some content")
}
