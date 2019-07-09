package model

import (
	"errors"
	"fmt"
	"net"
)

const (
	RabbitMqHost   = "RABBITMQ_HOST"
	RabbitMqPort   = "RABBITMQ_PORT"
	RabbitMqUser   = "RABBITMQ_USER"
	RabbitMqPass   = "RABBITMQ_PASS"
	RabbitMqVHost  = "RABBITMQ_VHOST"
	MultiProbeHost = "MULTI_PROBE_HOST"
	MultiProbePort = "MULTI_PROBE_PORT"
	MetricsHost    = "METRICS_HOST"
	MetricsPort    = "METRICS_PORT"
)

type Adapter string

const (
	ModeCompose Adapter = "compose"
	ModeSwarm   Adapter = "swarm"
)

type NodeConfig struct {
	NodeConfig  map[string]NodeUserParams `json:"node_config"`
	Environment Environment               `json:"environment,omitempty"`
}

type NodeUserParams struct {
	Faucet TopologyBridgeFaucetSettingsJson `json:"faucet,omitempty"`
	Worker TopologyBridgeWorkerJson         `json:"worker"`
}

type Environment struct {
	DockerRegistry      string  `json:"docker_registry"`
	DockerPfBridgeImage string  `json:"docker_pf_bridge_image"`
	RabbitMqHost        string  `json:"rabbitmq_host"`
	RabbitMqUser        string  `json:"rabbitmq_user"`
	RabbitMqPass        string  `json:"rabbitmq_pass"`
	RabbitMqVHost       string  `json:"rabbitmq_vhost"`
	MultiProbeHost      string  `json:"multi_probe_host"`
	MetricsHost         string  `json:"metrics_host"`
	WorkerDefaultPort   int     `json:"worker_default_port"`
	GeneratorMode       Adapter `json:"generator_mode"`
}

func (p *NodeConfig) GetBridges(t *Topology, nodes []Node, WorkerDefaultPort int) ([]TopologyBridgeJson, error) {

	var (
		bridges []TopologyBridgeJson
		port    int
	)

	if len(nodes) == 0 {
		return nil, errors.New(fmt.Sprintf("missing nodes"))
	}

	i := 0
	for _, node := range nodes {

		port = WorkerDefaultPort + i

		nodeId := CreateServiceName(node.GetServiceName())

		var worker TopologyBridgeWorkerJson
		var faucet TopologyBridgeFaucetSettingsJson
		if nodeConfig, ok := p.NodeConfig[node.ID.Hex()]; ok {
			worker = nodeConfig.Worker
			faucet = nodeConfig.Faucet
		} else {
			return nil, errors.New(fmt.Sprintf("missing config data for node ID: %s", node.ID.Hex()))
		}

		bridges = append(bridges, TopologyBridgeJson{
			ID: CreateServiceName(nodeId),
			Label: TopologyBridgeLabelJson{
				ID:       CreateServiceName(nodeId),
				NodeId:   node.ID.Hex(),
				NodeName: node.Name,
			},
			Faucet: faucet,
			Worker: worker,
			Next:   node.GetNext(),
			// TODO: add multimode choice
			Debug: TopologyBridgeDebugJson{
				Port: port,
				Host: t.GetMultiNodeName(),
				Url:  fmt.Sprintf("http://%s:%d/status", t.GetMultiNodeName(), port),
			},
		})

		i++
	}

	return bridges, nil
}

func (e *Environment) GetEnvironment() (map[string]string, error) {
	var environment = make(map[string]string)

	if host, port, err := net.SplitHostPort(e.RabbitMqHost); err == nil {
		environment[RabbitMqHost] = host
		environment[RabbitMqPort] = port
		environment[RabbitMqUser] = e.RabbitMqUser
		environment[RabbitMqPass] = e.RabbitMqPass
		environment[RabbitMqVHost] = e.RabbitMqVHost
	}

	if host, port, err := net.SplitHostPort(e.MultiProbeHost); err == nil {
		environment[MultiProbeHost] = host
		environment[MultiProbePort] = port
	}

	if host, port, err := net.SplitHostPort(e.MetricsHost); err == nil {
		environment[MetricsHost] = host
		environment[MetricsPort] = port
	}

	return environment, nil
}
