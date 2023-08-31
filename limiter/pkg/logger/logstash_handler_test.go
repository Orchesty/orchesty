package logger

import (
	"encoding/json"
	"github.com/stretchr/testify/assert"
	"testing"
)

func TestLogStashFormatter_Format(t *testing.T) {
	f := logStashFormatter{appName: "limiter_app"}

	var fields = make(map[string]interface{})

	fields["notification_type"] = "test"

	data, err := f.Format(fields)

	if err != nil {
		assert.Failf(t, "LogStash formatter error: %s", err.Error())
	}

	var result = make(map[string]interface{})

	json.Unmarshal(data, &result)

	assert.Contains(t, result, "hostname")
	assert.Contains(t, result, "timestamp")
	assert.Contains(t, result, "notification_type")
	assert.Equal(t, "test", result["notification_type"])
	assert.Contains(t, result, "type")
	assert.Equal(t, "limiter_app", result["type"])
	assert.Len(t, result, 4)
}
