package tcp

import (
	"github.com/stretchr/testify/assert"
	"limiter/pkg/logger"
	"testing"
	"time"
)

type positiveLimiter struct{}

func (dec *positiveLimiter) Start() {}
func (dec *positiveLimiter) Stop()  {}
func (dec *positiveLimiter) IsFreeLimit(key string, time int, value int) (bool, error) {
	return true, nil
}

type negativeLimiter struct{}

func (dec *negativeLimiter) Start() {}
func (dec *negativeLimiter) Stop()  {}
func (dec *negativeLimiter) IsFreeLimit(key string, time int, value int) (bool, error) {
	return false, nil
}

// TestServer tests TcpServer healthCheck route
func TestServerHealthCheck(t *testing.T) {
	pos := positiveLimiter{}
	tcpServer := NewTcpServer(&pos, logger.GetNullLogger())
	go tcpServer.Start(3334)
	defer tcpServer.Stop()

	// waiting for servers to start
	time.Sleep(time.Millisecond * 20)

	resp, err := SendTcpPacket("localhost:3334", CreateTcpHealthCheckRequestContent("someId"))
	assert.Nil(t, err)
	assert.Equal(t, "pf-health-check;someId;ok", resp)
}

// TestServer tests TcpServer healthCheck route
func TestServerLimitCheck(t *testing.T) {
	posServer := NewTcpServer(&positiveLimiter{}, logger.GetNullLogger())
	negServer := NewTcpServer(&negativeLimiter{}, logger.GetNullLogger())
	go posServer.Start(3334)
	go negServer.Start(3335)
	defer posServer.Stop()
	defer negServer.Stop()

	// waiting for servers to start
	time.Sleep(time.Millisecond * 20)

	resp, err := SendTcpPacket("localhost:3334", CreateTcpCheckRequestContent("someId", "someKey", 10, 50))
	assert.Nil(t, err)
	assert.Equal(t, "pf-check;someId;ok", resp)

	resp, err = SendTcpPacket("localhost:3335", CreateTcpCheckRequestContent("someId", "someKey", 10, 50))
	assert.Nil(t, err)
	assert.Equal(t, "pf-check;someId;nok", resp)
}
