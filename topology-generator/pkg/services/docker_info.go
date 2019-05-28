package services

import (
	"fmt"

	"github.com/docker/docker/api/types"
	"github.com/docker/docker/api/types/filters"

	"topology-generator/pkg/docker_client"
)

func GetDockerTopologyInfo(docker *docker_client.DockerApiClient, status string, name string) ([]types.Container, error) {
	filterList := filters.NewArgs()

	filterList.Add("status", status)
	filterList.Add("label", fmt.Sprintf("com.docker.compose.project=%s", name))

	options := types.ContainerListOptions{
		Filters: filterList,
	}

	return docker.GetContainers(options)
}

func GetSwarmTopologyInfo(docker *docker_client.DockerApiClient, status string, name string) ([]types.Container, error) {
	filterList := filters.NewArgs()

	filterList.Add("status", status)
	filterList.Add("label", fmt.Sprintf("com.docker.stack.namespace=%s", name))

	options := types.ContainerListOptions{
		Filters: filterList,
	}

	return docker.GetContainers(options)
}
