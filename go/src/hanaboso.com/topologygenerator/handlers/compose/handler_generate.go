package compose

import (
	"net/http"
	"github.com/gorilla/mux"
	"hanaboso.com/topologygenerator/response"
	"fmt"
	"github.com/docker/docker/api/types"
	"hanaboso.com/topologygenerator/generator/topology_json"
	"github.com/spf13/viper"
	"hanaboso.com/topologygenerator/commands"
	"hanaboso.com/topologygenerator/generator/docker_compose"
	"log"
	"hanaboso.com/topologygenerator/model"
	"hanaboso.com/utils/topology"
)

func (h *DockerCompose) GenerateAction(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	var (
		topologyId     = vars["topologyId"]
		topologyEntity topology.Topology
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

	log.Printf("GenerateAction: %s", message)
	requestResponse := response.RequestResponse{Message: message, DockerInfo: containers}
	response.ResponseWithJSON(w, requestResponse.Prepare(), status)
}
