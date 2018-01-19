package swarm

import (
	"net/http"
	"github.com/gorilla/mux"
	"fmt"
	"github.com/docker/docker/api/types"
	"hanaboso.com/topologygenerator/managers/swarm"
	"log"
	"hanaboso.com/topologygenerator/response"
	"hanaboso.com/utils/topology"
)

func (h *Swarm) StopAction(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	var (
		topologyId = vars["topologyId"]
		topology   topology.Topology
		containers []types.Container

		message = "Missing topologyId parameter"
		status  = http.StatusNotFound
	)

	if len(topologyId) != 0 {
		topology, _ = h.Db.GetTopologyById(topologyId)
		status, message = swarm.Stop(topology)
	} else {
		message = fmt.Sprintf("Topology ID: %s. Not found", vars["topologyId"])
	}

	log.Printf("Swarm StopAction: %s", message)
	requestResponse := response.RequestResponse{Message: message, DockerInfo: containers}
	response.ResponseWithJSON(w, requestResponse.Prepare(), status)
}
