package worker

import (
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"net/http"
)

type workerContainer struct {
	null     Null
	http     Http
	batch    Batch
	userTask UserTask
}

var workers workerContainer

func Get(name enum.WorkerType) (types.Worker, error) {
	switch name {
	case enum.WorkerType_Null:
		return workers.null, nil
	case enum.WorkerType_Http:
		return workers.http, nil
	case enum.WorkerType_Batch:
		return workers.batch, nil
	case enum.WorkerType_UserTask:
		return workers.userTask, nil
	}

	return Null{}, fmt.Errorf("unknown worker type [%s]", name)
}

func InitializeWorkers(mongodb *mongo.MongoDb) {
	client := http.Client{}

	workers = workerContainer{
		null: Null{},
		http: Http{
			httpBeforeProcess: httpBeforeProcess{
				client: client,
			},
		},
		batch: Batch{
			httpBeforeProcess: httpBeforeProcess{
				client: client,
			},
		},
		userTask: UserTask{
			mongodb: mongodb,
		},
	}
}
