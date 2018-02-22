package compose

import (
	"fmt"
	"hanaboso/topologygenerator/log"
	"net/http"

	"hanaboso/topologygenerator/commands"
	"hanaboso/topologygenerator/generator/docker_compose"
	"hanaboso/topologygenerator/generator/topology_json"
	"hanaboso/topologygenerator/model"
	"hanaboso/topologygenerator/response"

	"github.com/gorilla/mux"
	"github.com/spf13/viper"
)

// GenerateAction creates topology files.
func (h *DockerCompose) GenerateAction(w http.ResponseWriter, r *http.Request) {
	/*var (
		topologyID     = vars["topologyId"]
		topologyEntity topology.Topology
		nodes          []topology.Node
		containers     []types.Container

		message string
		status  int
	)*/

	resp := func(msg string, status int) {
		log.Infof("GenerateAction: %s", msg)
		requestResponse := response.RequestResponse{Message: msg}
		response.ResponseWithJSON(w, requestResponse.Prepare(), status)
	}

	vars := mux.Vars(r)

	topologyID, ok := vars["topologyId"]
	if !ok {
		resp("Missing topologyId parameter", http.StatusNotFound)
		return
	}

	topologyEntity, err := h.Db.GetTopologyById(topologyID)
	if err != nil {
		resp(fmt.Sprintf("Topology[id=%s] not found. Reason: %v", topologyID, err), http.StatusNotFound)
		return
	}

	nodes, err := h.Db.GetNodesByTopologyId(topologyID)
	if err != nil {
		resp(fmt.Sprintf("Topology[id=%s] not found. Reason: %v", topologyID, err), http.StatusNotFound)
		return
	}

	topologyJSON, err := topology_json.Create(topologyEntity, nodes)
	if err != nil {
		panic(model.AppError{Message: err.Error(), Type: model.ACTIONS})
	}

	if err := commands.WriteFile(
		fmt.Sprintf("%s/%s", viper.GetString("generator.path"), topologyEntity.GetSaveDir()),
		"topology.json",
		topologyJSON,
	); err != nil {
		resp(fmt.Sprintf("Writing topology[id=%s, file=topology.json] failed. Reason: %v", topologyID, err), http.StatusInternalServerError)
		return
	}

	dockerCompose, err := docker_compose.Create(topologyEntity, nodes, viper.GetString("generator.mode"))
	if err != nil {
		panic(model.AppError{Message: err.Error(), Type: model.ACTIONS})
	}

	if err := commands.WriteFile(
		fmt.Sprintf("%s/%s", viper.GetString("generator.path"), topologyEntity.GetSaveDir()),
		"docker-compose.yml",
		dockerCompose,
	); err != nil {
		resp(fmt.Sprintf("Writing topology[id=%s, file=docker-compose.json] failed. Reason: %v", topologyID, err), http.StatusInternalServerError)
		return
	}

	resp(fmt.Sprintf("ID: %s", topologyID), http.StatusOK)
}
