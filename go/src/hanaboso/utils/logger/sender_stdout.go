package logger

import (
	"log"
)

type stdOutSender struct {
}

func (s *stdOutSender) Send(data []byte) {
	log.Println(string(data))
}

func NewStdOutSender() Sender {
	return &stdOutSender{}
}
