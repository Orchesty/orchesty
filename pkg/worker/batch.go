package worker

import (
	"encoding/json"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
)

type Batch struct {
	httpBeforeProcess
}

func (Batch) AfterProcess(node types.Node, dto *model.ProcessMessage) (model.ProcessResult, int) {
	published := 0

	var contents []interface{}
	err := json.Unmarshal(dto.GetBody(), &contents)
	if err != nil {
		return dto.Trash(err), 0
	}

	resultCode := dto.GetIntHeaderOrDefault(enum.Header_ResultCode, 0)
	parentProcessId, _ := dto.GetHeader(enum.Header_ProcessId)

	if resultCode != enum.ResultCode_CursorOnly {
		for _, publisher := range node.Followers() {
			for _, content := range contents {
				body, err := json.Marshal(content)
				if err != nil {
					return dto.Trash(err), 0
				}

				published++
				partial := dto.
					CopyWithBody(body).
					SetHeader(enum.Header_ParentProcessId, parentProcessId).
					SetHeader(enum.Header_ProcessId, newUuid()).
					SetHeader(enum.Header_PreviousNodeId, node.Id()).
					DeleteHeader(enum.Header_Cursor)
				if err := publisher.Publish(partial.IntoAmqp()); err != nil {
					return dto.Error(err), 0
				}
			}
		}
	}

	if resultCode == enum.ResultCode_CursorWithFollowers || resultCode == enum.ResultCode_CursorOnly {
		published++
		if err := node.CursorPublisher().Publish(dto.IntoOriginalAmqp()); err != nil {
			return dto.Error(err), 0
		}
	}

	return dto.Ok(), published
}
