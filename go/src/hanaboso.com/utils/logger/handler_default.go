package logger

import (
	"fmt"
	"log"
)

// DefaultHandler with sender and formater
type DefaultHandler struct {
	Sender    Sender
	Formatter Formatter
}

// Handle uses sender and formatter to log message
func (h *DefaultHandler) Handle(data map[string]interface{}) {

	formatData, err := h.Formatter.Format(data)

	if err != nil {
		log.Fatalln(fmt.Sprintf("Formatter error: %s", err))
	}

	h.Sender.Send(formatData)
}
