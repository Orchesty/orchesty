package rabbit

import (
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"limiter/pkg/enum"
	"limiter/pkg/model"
)

var id = 0

func TestMessage() *model.MessageDto {
	id++

	return &model.MessageDto{
		Headers: map[string]interface{}{
			enum.Header_LimitKey: "system|user;1;1",
			"id":                 id,
		},
		Body: "{}",
	}
}

func TestRepeatMessage() *model.MessageDto {
	id++

	return &model.MessageDto{
		Headers: map[string]interface{}{
			enum.Header_LimitKey:       "system|user;1;1",
			enum.Header_ResultCode:     enum.ResultCode_Repeat,
			enum.Header_RepeatInterval: 1,
			enum.Header_RepeatHops:     1,
			enum.Header_RepeatMaxHops:  2,
			"id":                       id,
		},
		Body: "{}",
	}
}

func TestJsonMessage() (rabbitmq.JsonMessageMock[model.MessageDto], int) {
	message := TestMessage()

	return rabbitmq.JsonMessageMock[model.MessageDto]{Content: message}, message.GetIntHeader("id")
}

func TestJsonRepeatedMessage() (rabbitmq.JsonMessageMock[model.MessageDto], int) {
	message := TestRepeatMessage()

	return rabbitmq.JsonMessageMock[model.MessageDto]{Content: message}, message.GetIntHeader("id")
}
