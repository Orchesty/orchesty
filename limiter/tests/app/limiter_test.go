package app

import (
	"github.com/hanaboso/go-utils/pkg/timex"
	"github.com/stretchr/testify/assert"
	"limiter/tests/rabbit"
	"testing"
	"time"
)

func TestLimitSingleMessage(t *testing.T) {
	sender, limit, _, stop := prepareMockApplication()
	defer stop()

	message, id := rabbit.TestJsonMessage()
	limit.Messages <- message

	outputMessage := <-sender
	assert.Equal(t, id, outputMessage.GetIntHeader("id"))
}

func TestLimitMultipleMessages(t *testing.T) {
	sender, limit, _, stop := prepareMockApplication()
	defer stop()

	message, id := rabbit.TestJsonMessage()
	message2, id2 := rabbit.TestJsonMessage()
	limit.Messages <- message
	limit.Messages <- message2

	now := timex.UnixMs()
	outputMessage := <-sender
	assert.Equal(t, id, outputMessage.GetIntHeader("id"))
	outputMessage = <-sender
	assert.Equal(t, id2, outputMessage.GetIntHeader("id"))
	passed := timex.UnixMs() - now
	assert.Greater(t, time.Duration(passed)*time.Millisecond, time.Second)
}
