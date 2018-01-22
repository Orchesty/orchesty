package logger

import (
	"testing"
	"encoding/json"
	"github.com/stretchr/testify/assert"
)

type mockSender struct {
	t *testing.T
}

func (m *mockSender) Send(data []byte) error {

	var result = make(map[string]interface{})

	json.Unmarshal(data, &result)

	assert.Contains(m.t, result, "severity")
	assert.Equal(m.t, "info", result["severity"])
	assert.Contains(m.t, result, "message")
	assert.Equal(m.t, "my-message", result["message"])
	assert.Contains(m.t, result, "timestamp")
	assert.Contains(m.t, result, "hostname")
	assert.Contains(m.t, result, "type")
	assert.Equal(m.t, "limiter", result["type"])
	assert.Contains(m.t, result, "notification_type")
	assert.Equal(m.t, "test", result["notification_type"])
	assert.Len(m.t, result, 6)

	return nil
}

func TestLogger_Log(t *testing.T) {

	l := NewLogger()
	l.AddHandler(NewLogStashHandler(&mockSender{t: t}))

	l.Log("info", "my-message", Context{"notification_type": "test"})
}
