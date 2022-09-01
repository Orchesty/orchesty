package worker

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"net/http"
	"time"
)

type httpBeforeProcess struct {
	client http.Client
}

type Http struct {
	httpBeforeProcess
	broadcastAfterProcess
}

func (h httpBeforeProcess) BeforeProcess(node types.Node, dto *model.ProcessMessage) model.ProcessResult {
	host := node.Settings().Url
	if !CanSend(host) {
		time.Sleep(delaySec * time.Second)
		return dto.Error(fmt.Errorf("sdk was unreachable, delaying message"))
	}

	dto.KeepRepeatHeaders = true
	dto.ClearHeaders()

	messageBody := model.MessageDto{
		Body:    string(dto.GetBody()),
		Headers: dto.Headers,
	}
	if messageBody.Headers == nil {
		messageBody.Headers = make(map[string]interface{})
	}

	for key, value := range node.Settings().Headers {
		messageBody.Headers[key] = fmt.Sprint(value)
	}

	marshaled, _ := json.Marshal(&messageBody)
	body := bytes.NewBuffer(marshaled)
	req, err := http.NewRequest("POST", node.Settings().ActionUrl(), body)
	if err != nil {
		return dto.Error(err)
	}

	// TODO configurable timeout
	ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
	defer cancel()
	req = req.WithContext(ctx)

	response, err := h.client.Do(req)
	if err != nil {
		Lock(host)
		return dto.Error(err)
	}
	defer response.Body.Close()

	dto.FromHttpResponse(response)
	if response.StatusCode > 500 {
		Lock(host)
		return dto.Error(fmt.Errorf("result status [%d]", response.StatusCode))
	} else if response.StatusCode >= 300 {
		return dto.Trash(
			fmt.Errorf(
				"result status [%d], message: %s",
				response.StatusCode,
				dto.GetHeaderOrDefault(enum.Header_ResultMessage, ""),
			),
		)
	}

	// Only check for result code existence -> process is outside http worker
	if _, err := dto.GetHeader(enum.Header_ResultCode); err != nil {
		return dto.Trash(err)
	}

	return dto.Ok()
}
