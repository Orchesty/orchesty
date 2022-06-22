package worker

import (
	"bytes"
	"context"
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/utils/stringx"
	"net/http"
	"strings"
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
		time.Sleep(delay)
		return dto.Error(fmt.Errorf("sdk was unreachable, delaying message"))
	}

	body := bytes.NewBuffer(dto.GetBody())
	req, err := http.NewRequest("POST", node.Settings().ActionUrl(), body)
	if err != nil {
		return dto.Error(err)
	}

	dto.KeepRepeatHeaders = true
	dto.ClearHeaders()
	for key, value := range dto.Headers {
		if strings.HasPrefix(key, model.HeaderPrefix) {
			req.Header.Set(key, fmt.Sprint(value))
		}
	}

	for key, value := range node.Settings().Headers {
		req.Header.Set(key, fmt.Sprint(value))
	}

	// TODO configurable timeout
	ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
	defer cancel()
	req = req.WithContext(ctx)

	response, err := h.client.Do(req)
	if err != nil {
		return dto.Error(err)
	}
	defer response.Body.Close()

	dto.FromHttpResponse(response)
	if response.StatusCode > 500 {
		Lock(host)
		return dto.Error(fmt.Errorf("result status [%d]", response.StatusCode))
	} else if response.StatusCode >= 300 {
		dto.SetHeader(enum.Header_ResultMessage, stringx.Truncate(string(dto.Body), 200))
		return dto.Trash(fmt.Errorf("result status [%d], message: %s", response.StatusCode, string(dto.Body)))
	}

	// Only check for result code existence -> process is outside http worker
	if _, err := dto.GetHeader(enum.Header_ResultCode); err != nil {
		return dto.Trash(err)
	}

	return dto.Ok()
}
