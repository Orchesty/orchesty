package logger

import (
	"encoding/json"
	"testing"

	"github.com/stretchr/testify/assert"
)

type mockSenderForHandler struct {
	t *testing.T
}

func (s *mockSenderForHandler) Send(data []byte) {

	var result = make(map[string]interface{})

	json.Unmarshal(data, &result)

	assert.Contains(s.t, result, "timestamp")
	assert.Contains(s.t, result, "hostname")
	assert.Contains(s.t, result, "type")
	assert.Equal(s.t, "limiter", result["type"])
	assert.Len(s.t, result, 3)

}

func TestLogStashHandler_Handle(t *testing.T) {
	handler := NewLogStashHandler(&mockSenderForHandler{t: t}, "limiter")

	var fields = make(map[string]interface{})

	handler.Handle(fields)
}
