package logger

import (
	"encoding/json"
	"os"
	"time"
)

type logStashFormatter struct {
}

func (f *logStashFormatter) Format(data map[string]interface{}) ([]byte, error) {
	hostname, _ := os.Hostname()
	data["timestamp"] = time.Now().Unix()
	data["hostname"] = hostname

	if val, ok := data["type"]; !ok || val == "" {
		data["type"] = "limiter"
	}

	return json.Marshal(data)
}

// NewLogStashFormatter creates formatter with logstash pattern
func NewLogStashFormatter() Formatter {
	return &logStashFormatter{}
}
