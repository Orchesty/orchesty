package model

import (
	"fmt"
	"github.com/hanaboso/go-utils/pkg/intx"
	"limiter/pkg/enum"
)

type MessageDto struct {
	Headers map[string]interface{} `json:"headers" bson:"headers"`
	Body    string                 `json:"body" bson:"body"`
}

func (this MessageDto) GetHeader(key string) string {
	value, ok := this.Headers[key]
	if !ok {
		value = ""
	}

	return fmt.Sprintf("%s", value)
}

func (this MessageDto) GetIntHeader(key string) int {
	value, ok := this.Headers[key]
	if !ok {
		return 0
	}

	return intx.ParseAny(value)
}

func (this MessageDto) LimitKey() string {
	return this.GetHeader(enum.Header_LimitKey)
}

func (this MessageDto) RepeatDelay() int {
	repeated := this.GetIntHeader(enum.Header_ResultCode) == enum.ResultCode_Repeat
	if !repeated {
		return 0
	}

	return this.GetIntHeader(enum.Header_RepeatInterval)
}
