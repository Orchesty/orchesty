package docker

import (
	"github.com/docker/docker/client"
	"github.com/docker/docker/api/types"
	"context"
	"hanaboso.com/topologygenerator/model"
)

var cli *client.Client

func connect() {
	var err error
	cli, err = client.NewEnvClient()
	cli.ClientVersion()

	if err != nil {
		panic(model.AppError{Message: err.Error(), Type: model.DOCKER})
	}
}

func close() {
	if cli != nil {
		cli.Close()
	}
}

func containerList(containerOptions types.ContainerListOptions) []types.Container {

	defer close()

	if cli == nil {
		connect()
	}

	containers, err := cli.ContainerList(context.Background(), containerOptions)
	if err != nil {
		panic(model.AppError{Message: err.Error(), Type: model.DOCKER})
	}

	return containers
}
