package logger

import (
	"log"
)

type stdOutSender struct {
}

func (s *stdOutSender) Send(data []byte) {
	log.Println(string(data))
}

// NewStdOutSender creates new logger sender that prints everything on stdOut
func NewStdOutSender() Sender {
	return &stdOutSender{}
}
