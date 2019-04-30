package logger

import (
	"encoding/json"
	"github.com/stretchr/testify/assert"
	"os"
	"testing"
)

type mockSender struct {
	t *testing.T
}

func (m *mockSender) Send(data []byte) {

	var result = make(map[string]interface{})

	json.Unmarshal(data, &result)

	assert.Contains(m.t, result, "severity")
	assert.Equal(m.t, "info", result["severity"])
	assert.Contains(m.t, result, "message")
	assert.Equal(m.t, "my-message", result["message"])
	assert.Contains(m.t, result, "timestamp")
	assert.Contains(m.t, result, "hostname")
	assert.Contains(m.t, result, "type")
	assert.Equal(m.t, "limiter_app", result["type"])
	assert.Contains(m.t, result, "notification_type")
	assert.Equal(m.t, "test", result["notification_type"])
	assert.Len(m.t, result, 6)
}

func TestLogger_Log(t *testing.T) {
	os.Setenv("APP_NAME", "limiter_app")

	l := NewLogger()
	l.AddHandler(NewLogStashHandler(&mockSender{t: t}))

	l.Log("info", "my-message", Context{"notification_type": "test"})
}

type mockSenderMetrics struct {
	t *testing.T
}

func (m *mockSenderMetrics) Send(data []byte) {

	var result = make(map[string]interface{})

	json.Unmarshal(data, &result)

	assert.Contains(m.t, result, "severity")
	assert.Equal(m.t, "info", result["severity"])
	assert.Contains(m.t, result, "message")
	assert.Equal(m.t, "", result["message"])
	assert.Contains(m.t, result, "timestamp")
	assert.Contains(m.t, result, "hostname")
	assert.Contains(m.t, result, "type")
	assert.Equal(m.t, "limiter_app", result["type"])
	assert.Contains(m.t, result, "guid")
	assert.Equal(m.t, "#123", result["guid"])
	assert.Contains(m.t, result, "system_key")
	assert.Equal(m.t, "my-system", result["system_key"])
	assert.Len(m.t, result, 7)
}

func TestLogger_Metrics(t *testing.T) {
	os.Setenv("APP_NAME", "limiter_app")

	l := NewLogger()
	l.AddHandler(NewLogStashHandler(&mockSenderMetrics{t: t}))

	l.Metrics("my-system|#123", "", nil)
}
