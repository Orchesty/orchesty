package bridge

import (
	"fmt"
	"github.com/hanaboso/go-utils/pkg/intx"
	"limiter/pkg/enum"
)

type RequestMessage struct {
	MessageId string                 `json:"messageId"`
	Headers   map[string]interface{} `json:"headers"`
	Body      string                 `json:"body"`
	Published int64                  `json:"published"`
}

type ResultMessage struct {
	MessageId  string `json:"messageId"`
	LimiterKey string `json:"limiterKey"`
	Ok         bool   `json:"ok"`
}

func (this RequestMessage) GetHeader(key string) string {
	value, ok := this.Headers[key]
	if !ok {
		value = ""
	}

	return fmt.Sprintf("%s", value)
}

func (this RequestMessage) GetIntHeader(key string) int {
	value, ok := this.Headers[key]
	if !ok {
		return 0
	}

	return intx.ParseAny(value)
}

func (this RequestMessage) BridgeUrl() string {
	return fmt.Sprintf("http://topology-%s:8000/api/process", this.GetHeader(enum.Header_TopologyId))
}
