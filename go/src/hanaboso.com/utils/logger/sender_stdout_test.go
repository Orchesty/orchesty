package logger

import (
	"testing"
)

func TestStdOutSender_Send(t *testing.T) {
	s := stdOutSender{}
	s.Send([]byte("test output sender"))
}
