package docker

import (
	"github.com/docker/docker/api/types"
	"github.com/docker/docker/api/types/filters"
	"fmt"
)

func ComposeTopologyInfo(projectName string, status string) []types.Container {

	filters := filters.NewArgs(
		filters.KeyValuePair{Key: "status", Value: status},
		filters.KeyValuePair{Key: "label", Value: fmt.Sprintf("com.docker.compose.project=%s", projectName)},
	)

	options := types.ContainerListOptions{
		Filters: filters,
	}

	containers := ContainerList(options)

	return containers
}

func SwarmTopologyInfo(projectName string, status string) []types.Container {

	filters := filters.NewArgs(
		filters.KeyValuePair{Key: "status", Value: status},
		filters.KeyValuePair{Key: "label", Value: fmt.Sprintf("com.docker.stack.namespace=%s", projectName)},
	)

	options := types.ContainerListOptions{
		Filters: filters,
	}

	containers := ContainerList(options)

	return containers
}
