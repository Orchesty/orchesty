package compose

import (
	"fmt"
	"net/http"

	"hanaboso/topologygenerator/commands"
	"hanaboso/utils/topology"

	"github.com/spf13/viper"
)

func GetDstDir(topology *topology.Topology) string {
	dstDir := fmt.Sprintf("%s/%s", viper.GetString("generator.path"), topology.GetSaveDir())

	return dstDir
}

func GetStopCommand(topology *topology.Topology) (string, []string) {

	dstDir := GetDstDir(topology)
	config := fmt.Sprintf("%s/docker-compose.yml", dstDir)

	var args = []string{"-f", config, "down"}

	return "docker-compose", args
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
