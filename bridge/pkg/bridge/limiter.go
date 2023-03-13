package bridge

import (
	"fmt"
	"github.com/hanaboso/go-utils/pkg/stringx"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/rabbit"
	"strings"
)

type limiter struct {
	publisher types.Publisher
}

func parseLimitApplications(limitKey string) map[string]struct{} {
	limits := make(map[string]struct{})
	parts := strings.Split(limitKey, ";")
	for i := 0; i < len(parts); i += 3 {
		if len(parts) > i+2 {
			keys := strings.Split(parts[i], "|")
			limits[stringx.FromArray(keys, 1)] = struct{}{}
		}
	}

	return limits
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

	// Check that current Node's Application is limited
	limitedApplications := parseLimitApplications(limitHeader)
	application := node.Application()
	if application == "" {
		return dto.Ok()
	}
	if _, ok := limitedApplications[application]; !ok {
		return dto.Ok()
	}

	dto.SetHeader(enum.Header_LimitReturnExchange, fmt.Sprintf("node.%s.hx", node.Id()))
	dto.SetHeader(enum.Header_LimitReturnRoutingKey, "1") // TODO routing key based on shard
	dto.SetHeader(enum.Header_Application, application)

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
