package swarm

import (
	"fmt"
	"net/http"

	"hanaboso/topologygenerator/commands"
	"hanaboso/topologygenerator/generator/docker_compose"
	"hanaboso/utils/topology"
)

func GetStopCommand(topology *topology.Topology) (string, []string) {

	var args = []string{"stack", "rm", docker_compose.GetTopologyPrefix(topology)}
	return "docker", args
}

func Stop(topology *topology.Topology) (int, string) {

	var (
		status  int
		message string
	)

	if topology.ID.Hex() == "" {
		return http.StatusInternalServerError, "Topology missing"
	}

	command, args := GetStopCommand(topology)
	_, err, stdErr := commands.Execute(command, args...)

	if err != nil {
		status = http.StatusInternalServerError
		message = fmt.Sprintf("%s [%s]", err.Error(), stdErr)
	} else {
		status = http.StatusOK
		message = fmt.Sprintf("ID: %s", topology.ID.Hex())
	}

	return status, message
}
