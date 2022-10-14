package bridge

import (
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"strings"
)

type limiter struct {
	publisher types.Publisher
	// TODO socket connection, prediction cache
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
		return dto.Ok()
	}

	limiters := make(map[string][]string)
	// Format: key|group;time;amount;key2|group2;time;amount
	limiterParsed := strings.Split(limitHeader, ";")
	for i := 0; i < len(limiterParsed); i += 3 {
		if len(limiterParsed) > i+2 {
			keys := strings.Split(limiterParsed[i], "|")
			limiters[keys[0]] = append(
				limiters[keys[0]],
				fmt.Sprintf("%s;%s;%s", limiterParsed[i], limiterParsed[i+1], limiterParsed[i+2]),
			)
		}
	}

	if _, ok := limiters[node.Application()]; !ok {
		return dto.Ok()
	}

	// TODO socket check && predictions are disabled so it simply sends each message directly to queue
	dto.SetHeader(enum.Header_LimitReturnExchange, fmt.Sprintf("node.%s.hx", node.Id()))
	dto.SetHeader(enum.Header_LimitReturnRoutingKey, "1") // TODO routing key based on shard
	dto.SetHeader(enum.Header_LimitMessageFromLimiter, "1")

	// Overrides limit key for excluding irrelevant applications
	dto.SetHeader(enum.Header_LimitKey, strings.Join(limiters[node.Application()], ";"))
	dto.SetHeader(enum.Header_LimitKeyBase, limitHeader)

	return l.publish(dto)
}

func (l *limiter) publish(dto *model.ProcessMessage) model.ProcessResult {
	dto.KeepRepeatHeaders = true
	if err := l.publisher.Publish(dto.IntoAmqp()); err != nil {
		return dto.Error(err)
	}

	return dto.Pending()
}

func newLimiter(publisher types.Publisher) *limiter {
	return &limiter{
		publisher: publisher,
	}
}
