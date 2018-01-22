package logger

import (
	"log"
	"fmt"
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
	data["type"] = "limiter"

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

func NewLogStashHandler(sender Sender) Handler {
	return &logStashHandler{sender, &logStashFormatter{}}
}
