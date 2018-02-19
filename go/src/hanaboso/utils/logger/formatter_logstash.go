package logger

import (
	"os"
	"time"
	"encoding/json"
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
