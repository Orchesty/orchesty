package model

import (
	"fmt"
	"net/url"
	"os"
	"strings"
)

const (
	passPrefix = "APP_PASS_"
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

// Limits set resource limits
type Limits struct {
	Memory string `json:"memory"`
	CPU    string `json:"cpu"`
}

// Requests set resource limits
type Requests struct {
	Memory string `json:"memory"`
	CPU    string `json:"cpu"`
}

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
	RabbitMqHost      string   `json:"rabbitmq_host"`
	RabbitMqUser      string   `json:"rabbitmq_user"`
	RabbitMqPass      string   `json:"rabbitmq_pass"`
	RabbitMqVHost     string   `json:"rabbitmq_vhost"`
	MetricsDsn        string   `json:"metrics_dsn"`
	MongodbDsn        string   `json:"mongodb_dsn"`
	MongodbDb         string   `json:"mongodb_db"`
	MetricsService    string   `json:"metrics_service"`
	WorkerDefaultPort int      `json:"worker_default_port"`
	GeneratorMode     Adapter  `json:"generator_mode"`
	Limits            Limits   `json:"limits"`
	Requests          Requests `json:"requests"`
	UdpLoggerUrl      string   `json:"udp_logger_url"`
}

func (p *NodeConfig) GetTopologyJson(t *Topology, nodes []Node) (TopologyJson, error) {
	topology := TopologyJson{
		Id:       t.ID.Hex(),
		Name:     t.Name,
		Nodes:    make([]NodeJson, len(nodes)),
		RabbitMq: make([]RabbitMqServer, 1),
	}

	if len(nodes) == 0 {
		return topology, fmt.Errorf("missing nodes")
	}

	login := ""
	if p.Environment.RabbitMqUser != "" || p.Environment.RabbitMqPass != "" {
		login = fmt.Sprintf("%s:%s@", p.Environment.RabbitMqUser, p.Environment.RabbitMqPass)
	}
	if p.Environment.RabbitMqVHost == "" {
		p.Environment.RabbitMqVHost = "/"
	}

	topology.RabbitMq[0] = RabbitMqServer{
		Dsn: fmt.Sprintf("amqp://%s%s/%s",
			login,
			p.Environment.RabbitMqHost,
			url.PathEscape(p.Environment.RabbitMqVHost)),
	}

	var worker TopologyBridgeWorkerJSON
	for i, node := range nodes {
		if nodeConfig, ok := p.NodeConfig[node.ID.Hex()]; ok {
			worker = nodeConfig.Worker
		} else {
			return topology, fmt.Errorf("missing config data for node Id: [%s]", node.ID.Hex())
		}

		nodeJson := NodeJson{
			Id:        node.ID.Hex(),
			Name:      node.Name,
			Worker:    worker.Type,
			Followers: make([]NodeJsonFollower, len(node.Next)),
			Settings: NodeSettingsJson{
				Url:        fmt.Sprintf("http://%s:%d", worker.Settings.Host, worker.Settings.Port),
				ActionPath: worker.Settings.ProcessPath,
				TestPath:   worker.Settings.StatusPath,
				Headers:    worker.Settings.Headers,
				Method:     worker.Settings.Method,
				// Bridge
				Timeout:        worker.Settings.Timeout,
				RabbitPrefetch: worker.Settings.RabbitPrefetch,
				// Repeater
				RepeaterEnabled:  worker.Settings.RepeaterEnabled,
				RepeaterHops:     worker.Settings.RepeaterHops,
				RepeaterInterval: worker.Settings.RepeaterInterval,
				// UserTask
				UserTask: worker.Settings.UserTask,
				// Limiter
				LimiterValue:    worker.Settings.LimiterValue,
				LimiterInterval: worker.Settings.LimiterInterval,
			},
		}
		for j, follower := range node.Next {
			nodeJson.Followers[j] = NodeJsonFollower{
				Id:   follower.ID,
				Name: follower.Name,
			}
		}

		topology.Nodes[i] = nodeJson
	}

	return topology, nil
}

// GetEnvironment GetEnvironment
func (e *Environment) GetEnvironment() (map[string]string, error) {
	var environment = make(map[string]string)

	// TODO: add support for Influx
	environment["METRICS_DSN"] = e.MetricsDsn
	environment["MONGODB_DSN"] = e.MongodbDsn
	environment["MONGODB_DB"] = e.MongodbDb
	environment["UDP_LOGGER_URL"] = e.UdpLoggerUrl

	for _, env := range os.Environ() {
		if !strings.HasPrefix(env, passPrefix) {
			continue
		}

		vals := strings.Split(env, "=")
		if len(vals) != 2 {
			continue
		}

		trimmedKey := strings.Replace(vals[0], passPrefix, "", 1)
		environment[trimmedKey] = vals[1]
	}

	return environment, nil
}
