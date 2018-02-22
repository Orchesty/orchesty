package logger

import (
	"net"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestUDPSender_Send(t *testing.T) {

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
			break
		}
	}()

	s := NewUDPSender("localhost", "5120")
	s.Send([]byte("test"))

	<-quitTest
}
