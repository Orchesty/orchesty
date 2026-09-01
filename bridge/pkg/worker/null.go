package worker

import (
	"github.com/gofrs/uuid"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
)

type nullBeforeProcess struct{}
type broadcastAfterProcess struct{}

type Null struct {
	nullBeforeProcess
	broadcastAfterProcess
}

var locks map[string]bool

func (nullBeforeProcess) BeforeProcess(_ types.Node, dto *model.ProcessMessage) model.ProcessResult {
	dto.ClearHeaders()

	return model.OkResult(dto)
}

func (broadcastAfterProcess) AfterProcess(node types.Node, dto *model.ProcessMessage) (model.ProcessResult, int) {
	allowedFollowers, err := node.Followers().FilterAllowed(dto)
	if err != nil {
		return dto.Trash(err), 0
	}

	published := 0
	parentProcessId, _ := dto.GetHeader(enum.Header_ProcessId)

	for _, publisher := range allowedFollowers {
		published++
		dto.SetHeader(enum.Header_PreviousNodeId, node.Id())
		toSend := dto
		if len(allowedFollowers) > 1 {
			toSend = dto.
				Copy().
				SetHeader(enum.Header_ProcessId, newUuid()).
				SetHeader(enum.Header_ParentProcessId, parentProcessId)
		}

		if err := publisher.Publish(toSend.IntoAmqp()); err != nil {
			return model.ErrorResult(dto, err), 0
		}
	}

	return dto.Ok(), published
}

func newUuid() string {
	id, err := uuid.NewV4()
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	return id.String()
}
