package worker

import (
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
)

type UserTask struct {
	broadcastAfterProcess
	mongodb *mongo.MongoDb
}

func (u UserTask) BeforeProcess(node types.Node, dto *model.ProcessMessage) model.ProcessResult {
	state, err := dto.GetHeader(enum.Header_UserTaskState)
	// If header does not exists, it's first visit -> send to storage
	if err != nil {
		if _, err = u.mongodb.StoreUserTask(dto.Ok(), node.NodeName(), node.TopologyName()); err != nil {
			return dto.Error(err)
		}

		return dto.Pending()
	}

	dto.ClearHeaders()

	// Otherwise message is sent to followers/stopped
	if state == enum.UserTask_Accept {
		return dto.Ok()
	}

	return dto.Stop()
}
