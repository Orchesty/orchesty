package model

import (
	"fmt"
	"net"
)

const (
	//RabbitDsn      = "RABBITMQ_DSN"

	// RabbitMqHost RabbitMqHost
	RabbitMqHost = "RABBITMQ_HOST"
	// RabbitMqPort RabbitMqPort
	RabbitMqPort = "RABBITMQ_PORT"
	// RabbitMqUser RabbitMqUser
	RabbitMqUser = "RABBITMQ_USER"
	// RabbitMqPass RabbitMqPass
	RabbitMqPass = "RABBITMQ_PASS"
	// RabbitMqVHost RabbitMqVHost
	RabbitMqVHost = "RABBITMQ_VHOST"
	// MultiProbeHost MultiProbeHost
	MultiProbeHost = "MULTI_PROBE_HOST"
	// MultiProbePort MultiProbePort
	MultiProbePort = "MULTI_PROBE_PORT"
	// MetricsHost MetricsHost
	MetricsHost = "METRICS_HOST"
	// MetricsPort MetricsPort
	MetricsPort = "METRICS_PORT"
	// MetricsService MetricsService
	MetricsService = "METRICS_SERVICE"
)

// Adapter Adapter
type Adapter string

const (
	// ModeCompose ModeCompose
	ModeCompose Adapter = "compose"
	// ModeSwarm ModeSwarm
	ModeSwarm Adapter = "swarm"
	// ModeKubernetes ModeKubernetes
	ModeKubernetes Adapter = "k8s"
)

// NodeConfig NodeConfig
type NodeConfig struct {
	NodeConfig  map[string]NodeUserParams `json:"node_config"`
	Environment Environment               `json:"environment,omitempty"`
}

// NodeUserParams NodeUserParams
type NodeUserParams struct {
	Faucet TopologyBridgeFaucetSettingsJSON `json:"faucet,omitempty"`
	Worker TopologyBridgeWorkerJSON         `json:"worker"`
}

// Environment Environment
type Environment struct {
	DockerRegistry      string `json:"docker_registry"`
	DockerPfBridgeImage string `json:"docker_pf_bridge_image"`
	//RabbitMqDsn         string  `json:"rabbitmq_dsn"`
	RabbitMqHost      string  `json:"rabbitmq_host"`
	RabbitMqUser      string  `json:"rabbitmq_user"`
	RabbitMqPass      string  `json:"rabbitmq_pass"`
	RabbitMqVHost     string  `json:"rabbitmq_vhost"`
	MultiProbeHost    string  `json:"multi_probe_host"`
	MetricsHost       string  `json:"metrics_host"`
	MetricsPort       string  `json:"metrics_port"`
	MetricsService    string  `json:"metrics_service"`
	WorkerDefaultPort int     `json:"worker_default_port"`
	GeneratorMode     Adapter `json:"generator_mode"`
}

// GetBridges GetBridges
func (p *NodeConfig) GetBridges(t *Topology, nodes []Node, WorkerDefaultPort int) ([]TopologyBridgeJSON, error) {

	var (
		bridges []TopologyBridgeJSON
		port    int
	)

	if len(nodes) == 0 {
		return nil, fmt.Errorf("missing nodes")
	}

	i := 0
	for _, node := range nodes {

		port = WorkerDefaultPort + i

		nodeID := CreateServiceName(node.GetServiceName())

		var worker TopologyBridgeWorkerJSON
		var faucet TopologyBridgeFaucetSettingsJSON
		if nodeConfig, ok := p.NodeConfig[node.ID.Hex()]; ok {
			worker = nodeConfig.Worker
			faucet = nodeConfig.Faucet
		} else {
			return nil, fmt.Errorf("missing config data for node ID: %s", node.ID.Hex())
		}

		bridges = append(bridges, TopologyBridgeJSON{
			ID: CreateServiceName(nodeID),
			Label: TopologyBridgeLabelJSON{
				ID:       CreateServiceName(nodeID),
				NodeID:   node.ID.Hex(),
				NodeName: node.Name,
			},
			Faucet: faucet,
			Worker: worker,
			Next:   node.GetNext(),
			// TODO: add multimode choice
			Debug: TopologyBridgeDebugJSON{
				Port: port,
				Host: t.GetMultiNodeName(),
				URL:  fmt.Sprintf("http://%s:%d/status", t.GetMultiNodeName(), port),
			},
		})

		i++
	}

	return bridges, nil
}

// GetEnvironment GetEnvironment
func (e *Environment) GetEnvironment() (map[string]string, error) {
	var environment = make(map[string]string)
	var err error
	//environment[RabbitDsn] = e.RabbitMqDsn
	if host, port, err := net.SplitHostPort(e.RabbitMqHost); err == nil {
		environment[RabbitMqHost] = host
		environment[RabbitMqPort] = port
		environment[RabbitMqUser] = e.RabbitMqUser
		environment[RabbitMqPass] = e.RabbitMqPass
		environment[RabbitMqVHost] = e.RabbitMqVHost
	}
	if err != nil {
		return nil, fmt.Errorf("Error splitting RabbitMqHost. Reason: %v", err)
	}
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
