package logger

import (
	"log"
)

type stdOutSender struct {
}

func (s *stdOutSender) Send(data []byte) error {
	log.Println(string(data))
	return nil
}

func NewStdOutSender() Sender {
	return &stdOutSender{}
}
