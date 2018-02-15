package logger

import (
	"log"
	"fmt"
)

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
