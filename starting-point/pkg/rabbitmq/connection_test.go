package rabbitmq

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestConnect(t *testing.T) {
	c := getConnection()
	c.Connect()
}

func TestDeclare(t *testing.T) {
	q := GetProcessCounterQueue()
	c := getConnection()

	c.Connect()
	c.Declare(q)
}

func TestDisconnect(t *testing.T) {
	c := getConnection()

	c.Connect()
	c.Disconnect()
}

func TestGetChannel(t *testing.T) {
	c := getConnection()

	c.Connect()
	ch := c.GetChannel("new")
	ch2 := c.GetChannel("new")

	assert.ObjectsAreEqual(ch, ch2)
}

func TestCreateChannel(t *testing.T) {
	c := getConnection()

	c.Connect()
	ch := c.CreateChannel("new")
	ch2 := c.GetChannel("new")

	assert.ObjectsAreEqual(ch, ch2)
}

func TestGetRestartChan(t *testing.T) {
	c := getConnection()

	c.Connect()
	ch := c.GetRestartChan()

	assert.IsType(t, make(chan bool), ch)
}

func TestCloseChannel(t *testing.T) {
	c := getConnection()

	c.Connect()
	c.CreateChannel("new")
	c.CloseChannel("new")
}

func TestConnClearChannels(t *testing.T) {
	c := getConnection()

	c.Connect()
	c.CreateChannel("new")
	c.CreateChannel("new2")
	c.ClearChannels()
}
