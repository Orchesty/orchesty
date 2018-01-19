package logger

import (
	"testing"
	"net"
	"github.com/stretchr/testify/assert"
)

func TestUpdSender_Send(t *testing.T) {

	quitTest := make(chan bool)

	addr, err := net.ResolveUDPAddr("udp", "localhost:5120")

	if err != nil {
		assert.Failf(t, "Could not resolve udp addr. Error: %s", err.Error())
	}

	conn, err := net.ListenUDP("udp", addr)

	if err != nil {
		assert.Failf(t, "Could not listen udp connection. Error: %s", err.Error())
	}
	defer conn.Close()

	go func() {
		buf := make([]byte, 1024)

		for {
			n, _, err := conn.ReadFromUDP(buf)
			assert.Equal(t, "test", string(buf[0:n]))

			if err != nil {
				assert.Failf(t, "Udp read error. Error: %s", err.Error())
			}

			quitTest <- true
		}
	}()

	s := NewUpdSender("localhost", "5120")
	s.Send("test")

	<-quitTest
}

func TestNewUpdSender(t *testing.T) {
	s := NewUpdSender("xyz", "5120")
	err := s.Send("test")

	assert.NotNil(t, err)
}
