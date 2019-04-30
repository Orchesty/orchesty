package logger

import (
	"encoding/json"
	"fmt"
	"log"
	"os"
	"time"
)

type logStashFormatter struct {
	appName string
}

func (f *logStashFormatter) Format(data map[string]interface{}) ([]byte, error) {
	hostname, _ := os.Hostname()
	data["timestamp"] = time.Now().Unix()
	data["hostname"] = hostname

	if val, ok := data["type"]; !ok || val == "" {
		data["type"] = f.appName
	}

	return json.Marshal(data)
}

type logStashHandler struct {
	sender    Sender
	formatter Formatter
}

func (h *logStashHandler) Handle(data map[string]interface{}) {

	formatData, err := h.formatter.Format(data)

	if err != nil {
		log.Fatalln(fmt.Sprintf("Formatter error: %s", err))
	}

	h.sender.Send(formatData)
}

// NewLogStashHandler returns new LogstashHandler
func NewLogStashHandler(sender Sender) Handler {
	return &logStashHandler{sender, &logStashFormatter{appName: os.Getenv("APP_NAME")}}
}
