package services

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson/primitive"

	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
)

func TestGetDockerServices(t *testing.T) {
	t.Run("Test get docker services", func(t *testing.T) {
		t.Run("Get all docker services", getAllDockerServices)
	})
}

func getAllDockerServices(t *testing.T) {
	t.SkipNow()

	topology := getTestTopology()
	nodeConfig := model.NodeConfig{
		NodeConfig:  getNodeConfigs(),
		Environment: getEnvironment(model.ModeCompose),
	}

	generatorConfig := config.GeneratorConfig{
		Path:              "/srv/app/topology",
		TopologyPath:      "/srv/app/topology.json",
		ProjectSourcePath: "/opt/srv",
		Mode:              model.ModeCompose,
		Prefix:            "dev",
		Network:           "test",
		MultiNode:         false,
		WorkerDefaultPort: 8000,
	}

	expected := map[string]*model.Service{
		"5cc047dd4e9acc002a200c12-Start": {
			Image:       "",
			WorkingDir:  "",
			User:        "",
			Environment: nil,
			Networks:    nil,
			Volumes:     nil,
			Configs:     nil,
			Command:     "",
		},
		"5cc047dd4e9acc002a200c13-Webhook": {
			Image:       "",
			WorkingDir:  "",
			User:        "",
			Environment: nil,
			Networks:    nil,
			Volumes:     nil,
			Configs:     nil,
			Command:     "",
		},
		"5cc047dd4e9acc002a200c14-Xml_parser": {
			Image:       "",
			WorkingDir:  "",
			User:        "",
			Environment: nil,
			Networks:    nil,
			Volumes:     nil,
			Configs:     nil,
			Command:     "",
		},
	}

	ts := NewTopologyService(&topology, getTestNodes(), nodeConfig, generatorConfig)

	result, err := ts.getDockerServices(model.ModeCompose)

	assert.Equal(t, expected, result)
	assert.Nil(t, err)
}

func getTestTopology() model.Topology {

	id, _ := primitive.ObjectIDFromHex("5cc0474e4e9acc00282bb942")

	return model.Topology{
		ID:         id,
		Name:       "test",
		Version:    1,
		Descr:      "main topology test",
		Visibility: "draft",
		Status:     "New",
		Enabled:    false,
		Bpmn:       "",
		RawBpmn:    "",
		Deleted:    false,
	}
}

func getTestNodes() []model.Node {

	var nodes = make([]model.Node, 0)

	id, _ := primitive.ObjectIDFromHex("5cc047dd4e9acc002a200c12")
	var next = []model.NodeNext{
		{
			ID:   "5cc047dd4e9acc002a200c14",
			Name: "Xml_parser",
		},
	}

	nodes = append(nodes, model.Node{
		ID:       id,
		Name:     "Start",
		Topology: "5cc0474e4e9acc00282bb942",
		Next:     next,
		Type:     "start",
		Handler:  "event",
		Enabled:  true,
		Deleted:  false,
	})

	id, _ = primitive.ObjectIDFromHex("5cc047dd4e9acc002a200c13")
	nodes = append(nodes, model.Node{
		ID:       id,
		Name:     "Webhook",
		Topology: "5cc0474e4e9acc00282bb942",
		Type:     "webhook",
		Handler:  "event",
		Enabled:  true,
		Deleted:  false,
	})

	id, _ = primitive.ObjectIDFromHex("5cc047dd4e9acc002a200c14")
	next = []model.NodeNext{
		{
			ID:   "5cc047dd4e9acc002a200c13",
			Name: "Webhook",
		},
	}

	nodes = append(nodes, model.Node{
		ID:       id,
		Name:     "Xml_parser",
		Topology: "5cc0474e4e9acc00282bb942",
		Next:     next,
		Type:     "xml_parser",
		Handler:  "action",
		Enabled:  true,
		Deleted:  false,
	})

	return nodes
}

func getNodeConfigs() map[string]model.NodeUserParams {
	return map[string]model.NodeUserParams{
		"5cc047dd4e9acc002a200c12": {
			Worker: model.TopologyBridgeWorkerJson{
				Type: "worker.null",
				Settings: model.TopologyBridgeWorkerSettingsJson{
					PublishQueue: model.TopologyBridgeWorkerSettingsQueueJson{},
				},
			},
		},
		"5cc047dd4e9acc002a200c13": {
			Faucet: model.TopologyBridgeFaucetSettingsJson{
				Settings: map[string]int{
					"prefetch": 10,
				},
			},
			Worker: model.TopologyBridgeWorkerJson{
				Type: "worker.http",
				Settings: model.TopologyBridgeWorkerSettingsJson{
					Host:         "monolith-api",
					ProcessPath:  "/connector/Webhook/webhook",
					StatusPath:   "/connector/Webhook/webhook/test",
					Method:       "POST",
					Port:         80,
					Secure:       false,
					PublishQueue: model.TopologyBridgeWorkerSettingsQueueJson{},
				},
			},
		},
		"5cc047dd4e9acc002a200c14": {
			Worker: model.TopologyBridgeWorkerJson{
				Type: "worker.http_xml_parser",
				Settings: model.TopologyBridgeWorkerSettingsJson{
					Host:         "xml-parser-api",
					ProcessPath:  "/Xml_parser",
					StatusPath:   "/Xml_parser/test",
					Method:       "POST",
					Port:         80,
					PublishQueue: model.TopologyBridgeWorkerSettingsQueueJson{},
				},
			},
		},
	}
}

func getEnvironment(mode model.Adapter) model.Environment {
	return model.Environment{
		DockerRegistry:      "dkr.hanaboso.net/pipes/pipes",
		DockerPfBridgeImage: "pf-bridge:dev",
		RabbitMqHost:        "rabbitmq:5672",
		RabbitMqUser:        "guest",
		RabbitMqPass:        "guest",
		RabbitMqVHost:       "/",
		MultiProbeHost:      "multi-probe:8007",
		MetricsHost:         "kapacitor:9100",
		WorkerDefaultPort:   8808,
		GeneratorMode:       mode,
	}
}
