package server

import (
	"errors"
	"fmt"
	"topology-generator/pkg/model"
)

type body struct {
	Action string `json:"action"`
}

type Service struct {
	TopologyHandler
}

type Swarm Service
type DockerCompose Service
type Kubernetes Service

type TopologyHandler interface {
	GenerateAction(c *ContextWrapper)
	RunStopAction(c *ContextWrapper)
	DeleteAction(c *ContextWrapper)
	InfoAction(c *ContextWrapper)
	Close()
}

func GetHandlerAdapter(mode model.Adapter) (TopologyHandler, error) {
	switch mode {
	case model.ModeCompose:
		return &DockerCompose{}, nil
	case model.ModeSwarm:
		return &Swarm{}, nil
	case model.ModeKubernetes:
		return &Kubernetes{}, nil
	}

	return nil, errors.New(fmt.Sprintf("unknown topology generator mode: %s", mode))
}
