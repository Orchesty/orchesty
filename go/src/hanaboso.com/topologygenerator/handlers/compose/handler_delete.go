package compose

import (
	"fmt"
	"log"
	"net/http"

	"github.com/docker/docker/api/types"
	"github.com/gorilla/mux"
	"hanaboso.com/topologygenerator/managers/compose"
	"hanaboso.com/topologygenerator/response"
	"hanaboso.com/utils/topology"
)

func (h *DockerCompose) DeleteAction(w http.ResponseWriter, r *http.Request) {
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
			status, message = compose.Stop(topology)
			_, _ = compose.Delete(topology)
		} else {
			message = fmt.Sprintf("Topology ID: %s. Not found", vars["topologyId"])
		}
	}

	log.Printf("StopAction: %s", message)
	requestResponse := response.RequestResponse{Message: message, DockerInfo: containers}
	response.ResponseWithJSON(w, requestResponse.Prepare(), status)
}
