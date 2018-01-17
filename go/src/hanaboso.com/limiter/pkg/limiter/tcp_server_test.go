package limiter

import (
	"testing"
	"github.com/stretchr/testify/assert"
	"bufio"
	"fmt"
	"net"
)

type positiveLimiter struct {}
func (dec *positiveLimiter) Start() {}
func (dec *positiveLimiter) Stop() {}
func (dec *positiveLimiter) IsFreeLimit(key string, time int, value int) (bool, error) {
	return true, nil
}

type negativeLimiter struct {}
func (dec *negativeLimiter) Start() {}
func (dec *negativeLimiter) Stop() {}
func (dec *negativeLimiter) IsFreeLimit(key string, time int, value int) (bool, error) {
	return false, nil
}

// TestServer tests TcpServer healthCheck route
func TestServerHealthCheck(t *testing.T) {
	pos := positiveLimiter{}
	tcpServer := NewTcpServer(&pos)
	go tcpServer.Start(3334)
	defer tcpServer.Stop()

	conn, err := net.Dial("tcp", "localhost:3334")
	if err != nil {
		assert.Fail(t, "Could not create tcp connection.")
	}
	for {
		text := "pf-health-check;someRequestId\n"
		fmt.Fprintf(conn, text)
		// listen for reply
		response, _ := bufio.NewReader(conn).ReadString('\n')
		assert.Equal(t, "pf-health-check;someRequestId;ok", response)
		break
	}
}

// TestServer tests TcpServer healthCheck route
func TestServerLimitCheck(t *testing.T) {
	posServer := NewTcpServer(&positiveLimiter{})
	negServer := NewTcpServer(&negativeLimiter{})
	go posServer.Start(3334)
	go negServer.Start(3335)

	defer posServer.Stop()
	defer negServer.Stop()

	conn, err := net.Dial("tcp", "localhost:3334")
	if err != nil {
		assert.Fail(t, "Could not create tcp connection.")
	}
	for {
		fmt.Fprintf(conn, "pf-check;someRequestId;key;10;50\n")
		// listen for reply
		response, _ := bufio.NewReader(conn).ReadString('\n')
		assert.Equal(t, "pf-check;someRequestId;ok", response)
		break
	}

	conn, err = net.Dial("tcp", "localhost:3335")
	if err != nil {
		assert.Fail(t, "Could not create tcp message.")
	}
	for {
		fmt.Fprintf(conn, "pf-check;someRequestId;key;10;50\n")
		// listen for reply
		response, _ := bufio.NewReader(conn).ReadString('\n')
		assert.Equal(t, "pf-check;someRequestId;nok", response)
		break
	}
}
