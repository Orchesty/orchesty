package app

import (
	"context"
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"github.com/hanaboso/go-utils/pkg/timex"
	"github.com/stretchr/testify/assert"
	"limiter/pkg/enum"
	"limiter/pkg/model"
	"limiter/tests/rabbit"
	"sync"
	"testing"
	"time"
)

func TestRepeatSingleMessage(t *testing.T) {
	sender, _, repeat, stop := prepareMockApplication()
	defer stop()

	message, id := rabbit.TestJsonRepeatedMessage()
	repeat.Messages <- message

	now := timex.UnixMs()
	outputMessage := <-sender
	assert.Equal(t, id, outputMessage.GetIntHeader("id"))
	passed := timex.UnixMs() - now
	assert.Greater(t, time.Duration(passed)*time.Millisecond, time.Second)
}

func TestRepeatWithoutLimitMessage(t *testing.T) {
	sender, _, repeat, stop := prepareMockApplication()
	defer stop()

	message := rabbitmq.JsonMessageMock[model.MessageDto]{Content: &model.MessageDto{
		Headers: map[string]interface{}{
			enum.Header_ResultCode:     enum.ResultCode_Repeat,
			enum.Header_RepeatInterval: 1,
			enum.Header_RepeatHops:     1,
			enum.Header_RepeatMaxHops:  2,
			"id":                       666,
		},
		Body: "{}",
	}}

	repeat.Messages <- message

	now := timex.UnixMs()
	outputMessage := <-sender
	assert.Equal(t, 666, outputMessage.GetIntHeader("id"))
	passed := timex.UnixMs() - now
	assert.Greater(t, time.Duration(passed)*time.Millisecond, time.Second)
}

func TestRepeaterBeforeLimiter(t *testing.T) {
	messageProcessor, sender, limiterMock, repeaterMock := prepareMockApplicationNotStarted()

	message, id := rabbit.TestJsonMessage()
	message2, id2 := rabbit.TestJsonMessage()
	messageRepeat, idRepeat := rabbit.TestJsonRepeatedMessage()

	limiterMock.Messages <- message
	limiterMock.Messages <- message2
	limiterMock.Messages <- messageRepeat

	// Await for repeat message to become available
	time.Sleep(1100 * time.Millisecond)

	ctx, cancel := context.WithCancel(context.Background())
	go messageProcessor.Start(ctx, &sync.WaitGroup{})

	stopFunc := func() {
		limiterMock.Close()
		repeaterMock.Close()
		cancel()
	}
	defer stopFunc()

	outputMessage := <-sender
	assert.Equal(t, idRepeat, outputMessage.GetIntHeader("id"))

	outputMessage = <-sender
	assert.Equal(t, id, outputMessage.GetIntHeader("id"))

	outputMessage = <-sender
	assert.Equal(t, id2, outputMessage.GetIntHeader("id"))
}
