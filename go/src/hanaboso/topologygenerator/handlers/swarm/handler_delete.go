package swarm

import (
	"fmt"
	"hanaboso/topologygenerator/log"
	"net/http"

	"hanaboso/topologygenerator/managers/swarm"
	"hanaboso/topologygenerator/response"
	"hanaboso/utils/topology"

	"github.com/docker/docker/api/types"
	"github.com/gorilla/mux"
)

func (h *Swarm) DeleteAction(w http.ResponseWriter, r *http.Request) {
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
			status, message = swarm.Stop(topology)
			_, _ = swarm.Delete(topology)
		} else {
			message = fmt.Sprintf("Topology ID: %s. Not found", vars["topologyId"])
		}
	}

	log.Infof("DeleteAction: %s", message)
	requestResponse := response.RequestResponse{Message: message, DockerInfo: containers}
	response.ResponseWithJSON(w, requestResponse.Prepare(), status)
}
