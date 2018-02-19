package compose

import (
	"fmt"
	"log"
	"net/http"

	"hanaboso/topologygenerator/docker"
	"hanaboso/topologygenerator/response"
	"hanaboso/utils/topology"

	"github.com/docker/docker/api/types"
	"github.com/gorilla/mux"
)

func (h *DockerCompose) InfoAction(w http.ResponseWriter, r *http.Request) {
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
			status = http.StatusOK
			message = fmt.Sprintf("ID: %s", vars["topologyId"])

			containers = docker.ComposeTopologyInfo(topology.GetDockerName(), "running")
		} else {
			message = fmt.Sprintf("Topology ID: %s. Not found", vars["topologyId"])
		}
	}

	log.Printf("InfoAction: %s", message)
	requestResponse := response.RequestResponse{Message: message, DockerInfo: containers}
	response.ResponseWithJSON(w, requestResponse.Prepare(), status)
}
