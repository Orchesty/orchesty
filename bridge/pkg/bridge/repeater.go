package bridge

import (
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/rabbit"
	"strconv"
)

type repeater struct {
	publisher types.Publisher
}

func (r *repeater) publish(node types.Node, dto *model.ProcessMessage) model.ProcessResult {
	maxHops, err := dto.GetIntHeader(enum.Header_RepeatMaxHops)
	if err != nil {
		return dto.Trash(err)
	}

	_, err = dto.GetIntHeader(enum.Header_RepeatInterval)
	if err != nil {
		return dto.Trash(err)
	}

	hops := dto.GetIntHeaderOrDefault(enum.Header_RepeatHops, 1)
	hops++
	dto.SetHeader(enum.Header_RepeatHops, strconv.Itoa(hops))

	if hops > maxHops {
		messsage, _ := dto.GetHeader(enum.Header_ResultMessage)
		return dto.Trash(fmt.Errorf("max repeat limit reached, %s", messsage))
	}

	// TODO should be routingKey + exchange -> needs repeater rework
	dto.SetHeader(enum.Header_RepeatQueue, fmt.Sprintf("node.%s.1", node.Id()))
	dto.SetHeader(enum.Header_LimitReturnExchange, fmt.Sprintf("node.%s.hx", node.Id()))
	dto.SetHeader(enum.Header_LimitReturnRoutingKey, "1") // TODO routing key based on shard

	if err := r.publisher.Publish(dto.IntoOriginalAmqp()); err != nil {
		return dto.Error(err)
	}

	return dto.Pending()
}

func newRepeater(rabbitContainer rabbit.Container) repeater {
	return repeater{
		publisher: rabbitContainer.Repeater,
	}
}
