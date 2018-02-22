package logger

import (
	"encoding/json"
	"os"
	"time"
)

type logStashFormatter struct {
	app string
}

func (f *logStashFormatter) Format(data map[string]interface{}) ([]byte, error) {
	hostname, _ := os.Hostname()
	data["timestamp"] = time.Now().Unix()
	data["hostname"] = hostname

	if val, ok := data["type"]; !ok || val == "" {
		data["type"] = f.app
	}

	return json.Marshal(data)
}

// NewLogStashFormatter creates formatter with logstash pattern
func NewLogStashFormatter(app string) Formatter {
	return &logStashFormatter{app}
}
