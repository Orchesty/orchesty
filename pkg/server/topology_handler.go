package server

import (
	"fmt"
	"topology-generator/pkg/model"
)

type body struct {
	Action string `json:"action"`
}

// Service Service
type Service struct {
	TopologyHandler
}

// Swarm Swarm
type Swarm Service

// DockerCompose DockerCompose
type DockerCompose Service

// Kubernetes Kubernetes
type Kubernetes Service

// TopologyHandler TopologyHandler
type TopologyHandler interface {
	GenerateAction(c *ContextWrapper)
	GetHostAction(c *ContextWrapper)
	RunStopAction(c *ContextWrapper)
	DeleteAction(c *ContextWrapper)
	InfoAction(c *ContextWrapper)
	Close()
}

// GetHandlerAdapter GetHandlerAdapter
func GetHandlerAdapter(mode model.Adapter) (TopologyHandler, error) {
	switch mode {
	case model.ModeCompose:
		return &DockerCompose{}, nil
	case model.ModeSwarm:
		return &Swarm{}, nil
	case model.ModeKubernetes:
		return &Kubernetes{}, nil
	}

	return nil, fmt.Errorf("unknown topology generator mode: %s", mode)
}
