package types

import (
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/rs/zerolog/log"
	"strings"
)

// Separated to avoid Go' complains about import cycle

// Common interface for RabbitMq & InMemory publishers
type Publisher interface {
	Publish(amqp.Publishing) error
	PublishBatch(messages []amqp.Publishing) error
}

type Publishers map[string]Publisher

// Returns all or selected publishers should resultCode be set to enum.ResultCode_ForwardToQueue
func (p Publishers) FilterAllowed(dto *model.ProcessMessage) (Publishers, error) {
	code := dto.GetIntHeaderOrDefault(enum.Header_ResultCode, 0)
	if code != enum.ResultCode_ForwardToQueue {
		return p, nil
	}

	targetIds := TargetIds(dto)

	log.Info().
		Bool(enum.LogHeader_IsForUi, true).
		EmbedObject(dto).
		Msg(
			fmt.Sprintf(
				"%s [%s]",
				dto.GetHeaderOrDefault(enum.Header_ResultMessage, "forwarded to queues"),
				dto.GetHeaderOrDefault(enum.Header_ForceTargetQueue, ""),
			),
		)

	selected := make(Publishers, len(targetIds))
	for _, targetId := range targetIds {
		pub, ok := p[targetId]
		if ok {
			selected[targetId] = pub
		} else {
			return nil, fmt.Errorf("unknown follower of id [%s]", targetId)
		}
	}

	return selected, nil
}

// TargetIds returns follower ids selected by ResultCode_ForwardToQueue (force-target-queue header),
// or nil when the message is not forwarded to a subset of followers.
func TargetIds(dto *model.ProcessMessage) []string {
	if dto.GetIntHeaderOrDefault(enum.Header_ResultCode, 0) != enum.ResultCode_ForwardToQueue {
		return nil
	}

	targetStr := dto.GetHeaderOrDefault(enum.Header_ForceTargetQueue, "")
	if strings.HasPrefix(targetStr, "pipes.") {
		// Deprecated format: "pipes.topologyId.nodeId"
		parts := strings.Split(targetStr, ".")
		if len(parts) == 3 {
			return []string{parts[2]}
		}

		return []string{}
	}

	// New format: "nodeId,nodeId2,..."
	return strings.Split(targetStr, ",")
}
