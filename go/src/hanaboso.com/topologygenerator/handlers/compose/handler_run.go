package compose

import (
	"fmt"
	"log"
	"net/http"

	"github.com/docker/docker/api/types"
	"github.com/gorilla/mux"
	"github.com/spf13/viper"
	"hanaboso.com/topologygenerator/commands"
	"hanaboso.com/topologygenerator/docker"
	"hanaboso.com/topologygenerator/response"
	"hanaboso.com/utils/topology"
)

func (h *DockerCompose) RunAction(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	var (
		topologyId = vars["topologyId"]
		topology   *topology.Topology
		containers []types.Container

		message = "Missing topologyId parameter"
		status  = http.StatusNotFound
	)

	if len(topologyId) != 0 {

		topology, _ = h.Db.GetTopologyById(topologyId)
		if topology.ID.Hex() != "" {

			dstDir := fmt.Sprintf("%s/%s", viper.GetString("generator.path"), topology.GetSaveDir())

			config := fmt.Sprintf("%s/docker-compose.yml", dstDir)
			_, err, stdErr := commands.Execute(
				"docker-compose",
				"-f",
				config,
				"up",
				"-d",
			)

			if err != nil {
				status = http.StatusInternalServerError
				message = fmt.Sprintf("%s [%s]", err.Error(), stdErr)
			} else {
				status = http.StatusOK
				message = fmt.Sprintf("ID: %s", vars["topologyId"])
			}

			containers = docker.ComposeTopologyInfo(topology.GetDockerName(), "running")
		} else {
			message = fmt.Sprintf("Topology ID: %s. Not found", vars["topologyId"])
		}
	}

	log.Printf("RunAction: %s", message)
	requestResponse := response.RequestResponse{Message: message, DockerInfo: containers}
	response.ResponseWithJSON(w, requestResponse.Prepare(), status)
}
