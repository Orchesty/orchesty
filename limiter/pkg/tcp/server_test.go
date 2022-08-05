package tcp

import (
	"testing"
	"time"

	"github.com/stretchr/testify/assert"

	"limiter/pkg/logger"
)

type positiveLimiter struct{}

func (dec *positiveLimiter) Start() {}
func (dec *positiveLimiter) Stop()  {}
func (dec *positiveLimiter) IsFreeLimit(key string, time int, value int, groupKey string, groupTime int, groupValue int) (bool, error) {
	return true, nil
}

type negativeLimiter struct{}

func (dec *negativeLimiter) Start() {}
func (dec *negativeLimiter) Stop()  {}
func (dec *negativeLimiter) IsFreeLimit(key string, time int, value int, groupKey string, groupTime int, groupValue int) (bool, error) {
	return false, nil
}

// TestServer tests TcpServer healthCheck route
func TestServerHealthCheck(t *testing.T) {
	pos := positiveLimiter{}
	tcpServer := NewTCPServer(&pos, logger.GetNullLogger())
	fault := make(chan bool, 1)
	go tcpServer.Start("127.0.0.1:3334", fault)
	defer tcpServer.Stop()
	defer close(fault)

	// waiting for servers to start
	time.Sleep(time.Millisecond * 20)

	resp, err := SendTCPPacket("localhost:3334", CreateTCPHealthCheckRequestContent("someId"))
	assert.Nil(t, err)
	assert.Equal(t, "health-check;someId;ok", resp)
}

// TestServer tests TcpServer healthCheck route
func TestServerLimitCheck(t *testing.T) {
	posServer := NewTCPServer(&positiveLimiter{}, logger.GetNullLogger())
	negServer := NewTCPServer(&negativeLimiter{}, logger.GetNullLogger())
	faultPos := make(chan bool, 1)
	faultNeg := make(chan bool, 1)

	go posServer.Start("127.0.0.1:3334", faultPos)
	go negServer.Start("127.0.0.1:3335", faultNeg)
	defer posServer.Stop()
	defer negServer.Stop()
	defer close(faultPos)
	defer close(faultNeg)

	// waiting for servers to start
	time.Sleep(time.Millisecond * 20)

	resp, err := SendTCPPacket("localhost:3334", CreateTCPCheckRequestContent("someId", "someKey", 10, 50))
	assert.Nil(t, err)
	assert.Equal(t, "check;someId;ok", resp)

	resp, err = SendTCPPacket("localhost:3335", CreateTCPCheckRequestContent("someId", "someKey", 10, 50))
	assert.Nil(t, err)
	assert.Equal(t, "check;someId;nok", resp)
}
