package model

import (
	"errors"
	"fmt"
	"net"
)

const (
	RabbitDsn      = "RABBITMQ_DSN"
	MultiProbeHost = "MULTI_PROBE_HOST"
	MultiProbePort = "MULTI_PROBE_PORT"
	MetricsHost    = "METRICS_HOST"
	MetricsPort    = "METRICS_PORT"
	MetricsService = "METRICS_SERVICE"
)

type Adapter string

const (
	ModeCompose    Adapter = "compose"
	ModeSwarm      Adapter = "swarm"
	ModeKubernetes Adapter = "k8s"
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
	RabbitMqDsn         string  `json:"rabbitmq_dsn"`
	MultiProbeHost      string  `json:"multi_probe_host"`
	MetricsHost         string  `json:"metrics_host"`
	MetricsPort         string  `json:"metrics_port"`
	MetricsService      string  `json:"metrics_service"`
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

	environment[RabbitDsn] = e.RabbitMqDsn

	host, port, err := net.SplitHostPort(e.MultiProbeHost)
	if err != nil {
		return nil, fmt.Errorf("Error splitting MultiProbeHost. Reason: %v", err)
	}
	environment[MultiProbeHost] = host
	environment[MultiProbePort] = port

	environment[MetricsHost] = e.MetricsHost
	environment[MetricsPort] = e.MetricsPort
	environment[MetricsService] = "influx"
	if service := e.MetricsService; service != "" {
		environment[MetricsService] = service
	}

	return environment, nil
}
