package server

import (
	"errors"
	"fmt"

	"topology-generator/pkg/docker_client"
	"topology-generator/pkg/model"
	"topology-generator/pkg/storage"
)

type body struct {
	Action string `json:"action"`
}

type Swarm struct {
	mongo  *storage.MongoDefault
	docker *docker_client.DockerApiClient
	TopologyHandler
}

type DockerCompose struct {
	mongo  *storage.MongoDefault
	docker *docker_client.DockerApiClient
	TopologyHandler
}

type TopologyHandler interface {
	GenerateAction(c *contextWrapper)
	RunStopAction(c *contextWrapper)
	DeleteAction(c *contextWrapper)
	InfoAction(c *contextWrapper)
	Close()
}

func GetHandlerAdapter(mode model.Adapter, mongo *storage.MongoDefault, docker *docker_client.DockerApiClient) (TopologyHandler, error) {
	if mode == model.ModeCompose {
		return &DockerCompose{
			mongo:  mongo,
			docker: docker,
		}, nil
	} else if mode == model.ModeSwarm {
		return &Swarm{
			mongo:  mongo,
			docker: docker,
		}, nil
	}

	return nil, errors.New(fmt.Sprintf("unknown topology generator mode: %s", mode))
}

func loadTopologyData(topologyId string, mongo *storage.MongoDefault) (*model.Topology, error) {
	if topologyId == "" {
		return nil, errors.New("input parameter `topologyId` is empty")
	}

	topology, err := mongo.FindTopologyByID(topologyId)

	if err != nil {
		return nil, err
	}

	return topology, nil
}
