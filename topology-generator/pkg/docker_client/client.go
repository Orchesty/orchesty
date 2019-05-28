package docker_client

import (
	"context"
	"log"

	"github.com/docker/docker/api/types"
	"github.com/docker/docker/client"
)

type DockerApiClient struct {
	cli *client.Client
}

func connect() (*client.Client, error) {
	cli, err := client.NewEnvClient()

	if err != nil {
		log.Fatal(err)
		return nil, err
	}

	return cli, nil
}

func (d *DockerApiClient) GetContainers(options types.ContainerListOptions) ([]types.Container, error) {

	containers, err := d.cli.ContainerList(context.Background(), options)

	if err != nil {
		return nil, err
	}

	return containers, nil
}

func CreateClient() (*DockerApiClient, error) {
	cli, err := connect()
	if err != nil {
		return nil, err
	}

	return &DockerApiClient{cli: cli}, nil
}

func (d *DockerApiClient) Close() {
	if d.cli != nil {
		d.cli.Close()
	}
}
