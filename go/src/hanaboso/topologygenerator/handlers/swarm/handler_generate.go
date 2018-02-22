package swarm

import (
	"fmt"
	"hanaboso/topologygenerator/log"
	"net/http"

	"hanaboso/topologygenerator/commands"
	"hanaboso/topologygenerator/generator/docker_compose"
	"hanaboso/topologygenerator/generator/topology_json"
	"hanaboso/topologygenerator/model"
	"hanaboso/topologygenerator/response"
	"hanaboso/utils/topology"

	"github.com/docker/docker/api/types"
	"github.com/gorilla/mux"
	"github.com/spf13/viper"
)

func (h *Swarm) GenerateAction(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	var (
		topologyId     = vars["topologyId"]
		topologyEntity *topology.Topology
		nodes          []topology.Node
		containers     []types.Container

		message = "Missing topologyId parameter"
		status  = http.StatusNotFound
	)

	if len(topologyId) != 0 {

		topologyEntity, _ = h.Db.GetTopologyById(topologyId)
		nodes, _ = h.Db.GetNodesByTopologyId(topologyId)

		if topologyEntity.ID.Hex() != "" {
			topologyJson, err := topology_json.Create(topologyEntity, nodes)

			if err != nil {
				panic(model.AppError{Message: err.Error(), Type: model.ACTIONS})
			}

			err = commands.WriteFile(
				fmt.Sprintf("%s/%s", viper.GetString("generator.path"), topologyEntity.GetSaveDir()),
				"topology.json",
				topologyJson,
			)

			dockerCompose, err := docker_compose.Create(topologyEntity, nodes, viper.GetString("generator.mode"))

			err = commands.WriteFile(
				fmt.Sprintf("%s/%s", viper.GetString("generator.path"), topologyEntity.GetSaveDir()),
				"docker-compose.yml",
				dockerCompose,
			)

			if err != nil {
				//TODO: add panic
				status = http.StatusInternalServerError
				message = fmt.Sprintf("ID: %s - %s", topologyId, err.Error())
			} else {
				status = http.StatusOK
				message = fmt.Sprintf("ID: %s", vars["topologyId"])
			}

		} else {
			message = fmt.Sprintf("Topology ID: %s. Not found", vars["topologyId"])
		}
	}

	log.Infof("Swarm GenerateAction: %s", message)
	requestResponse := response.RequestResponse{Message: message, DockerInfo: containers}
	response.ResponseWithJSON(w, requestResponse.Prepare(), status)
}
