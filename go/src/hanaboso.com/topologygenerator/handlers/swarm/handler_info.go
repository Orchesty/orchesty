package swarm

import (
	"net/http"
	"github.com/gorilla/mux"
	"hanaboso.com/topologygenerator/response"
	"fmt"
	"github.com/docker/docker/api/types"
	"log"
	"hanaboso.com/topologygenerator/docker"
	"github.com/spf13/viper"
	"hanaboso.com/utils/topology"
)

func (h *Swarm) InfoAction(w http.ResponseWriter, r *http.Request) {
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
		if topology.ID.Hex() != "" {
			status = http.StatusOK
			message = fmt.Sprintf("ID: %s", vars["topologyId"])
			containers = docker.SwarmTopologyInfo(topology.GetSwarmName(viper.GetString("generator.topology-prefix")), "running")
		} else {
			message = fmt.Sprintf("Topology ID: %s. Not found", vars["topologyId"])
		}
	}

	log.Printf("Swarm InfoAction: %s", message)
	requestResponse := response.RequestResponse{Message: message, DockerInfo: containers}
	response.ResponseWithJSON(w, requestResponse.Prepare(), status)
}
