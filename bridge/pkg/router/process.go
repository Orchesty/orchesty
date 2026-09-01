package router

import (
	"encoding/json"
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/limiter"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/utils/timex"
	"github.com/julienschmidt/httprouter"
	"io/ioutil"
	"net/http"
)

type requestMessage struct {
	MessageId string                 `json:"messageId"`
	Headers   map[string]interface{} `json:"headers"`
	Body      string                 `json:"body"`
	Published int64                  `json:"published"`
}

func Process(writer http.ResponseWriter, request *http.Request, _ httprouter.Params, container Container) {
	var requestData requestMessage
	data, err := ioutil.ReadAll(request.Body)
	if err != nil {
		return
	}

	err = json.Unmarshal(data, &requestData)
	if err != nil {
		return
	}

	requestData.Headers[enum.Header_LimitMessageFromLimiter] = "1"
	dto := &model.ProcessMessage{
		Body:           []byte(requestData.Body),
		Headers:        requestData.Headers,
		Exchange:       requestData.getHeader(enum.Header_LimitReturnExchange),
		RoutingKey:     requestData.getHeader(enum.Header_LimitReturnRoutingKey),
		Ack:            func() error { return nil },
		Nack:           func() error { return nil },
		Published:      requestData.Published,
		ProcessStarted: timex.UnixMs(),
	}

	ok := container.BridgeSvc.Process(dto)

	response(writer, limiter.Message{
		MessageId:  requestData.MessageId,
		LimiterKey: requestData.getHeader(enum.Header_LimitKey),
		Ok:         ok,
	})
}

func (this requestMessage) getHeader(key string) string {
	value, _ := this.Headers[key]
	return fmt.Sprint(value)
}
