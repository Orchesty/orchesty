package docker

import (
	"context"
	"hanaboso/topologygenerator/model"
	"hanaboso/topologygenerator/log"

	"github.com/docker/docker/api/types"
	"github.com/docker/docker/client"
	"fmt"
)

var cli *client.Client

func connect() {
	var err error
	cli, err = client.NewEnvClient()

	if err != nil {
		log.Fatal(err)
		panic(model.AppError{Message: err.Error(), Type: model.DOCKER})
	}

	cli.ClientVersion()
}

func close() {
	if cli != nil {
		cli.Close()
	}
}

func ContainerList(containerOptions types.ContainerListOptions) []types.Container {

	defer close()

	if cli == nil {
		connect()
	}

	containers, err := cli.ContainerList(context.Background(), containerOptions)
	if err != nil {
		log.Fatal(err)
		fmt.Println(err.Error())
		panic(model.AppError{Message: err.Error(), Type: model.DOCKER})
	}

	return containers
}
