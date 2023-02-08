package bridge

import (
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/rabbit"
)

type limiter struct {
	publisher types.Publisher
}

func (l *limiter) process(node types.Node, dto *model.ProcessMessage) model.ProcessResult {
	if node.WorkerType() == enum.WorkerType_Null || node.WorkerType() == enum.WorkerType_UserTask {
		return dto.Ok()
	}

	limitHeader := dto.GetHeaderOrDefault(enum.Header_LimitKey, "")

	if limitHeader == "" {
		return dto.Ok()
	}

	returnedHeader := dto.GetHeaderOrDefault(enum.Header_LimitMessageFromLimiter, "")
	if returnedHeader != "" {
		dto.DeleteHeader(enum.Header_LimitMessageFromLimiter)
		dto.DeleteHeader(enum.Header_Application)
		return dto.Ok()
	}

	dto.SetHeader(enum.Header_LimitReturnExchange, fmt.Sprintf("node.%s.hx", node.Id()))
	dto.SetHeader(enum.Header_LimitReturnRoutingKey, "1") // TODO routing key based on shard
	dto.SetHeader(enum.Header_Application, node.Application())

	return l.publish(dto)
}

func (l *limiter) publish(dto *model.ProcessMessage) model.ProcessResult {
	dto.KeepRepeatHeaders = true
	if err := l.publisher.Publish(dto.IntoAmqp()); err != nil {
		return dto.Error(err)
	}

	return dto.Pending()
}

func newLimiter(rabbitContainer rabbit.Container) limiter {
	return limiter{
		publisher: rabbitContainer.Limiter,
	}
}
