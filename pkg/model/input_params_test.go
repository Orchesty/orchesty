package model

import (
	"encoding/json"
	"testing"

	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson/primitive"
)

func TestEnvironment_GetEnvironment(t *testing.T) {
	t.Run("Test get docker environment", func(t *testing.T) {
		t.Run("Get full environment", getFullEnvironment)
	})
}

func TestGetBridges_GetBridges(t *testing.T) {
	t.Run("Test getting bridges base on input data", func(t *testing.T) {
		t.Run("Get bridges", getBridges)
		t.Run("Get bridges missing nod config", getBridgesMissingConfig)
		t.Run("Get bridges empty nods", getBridgesEmptyNodes)
	})
}

func getBridgesMissingConfig(t *testing.T) {
	topology := getTestTopology()
	nodes := getTestNodes()

	config := getNodeConfigs()
	delete(config, "5cc047dd4e9acc002a200c14")

	nodeConfig := NodeConfig{
		NodeConfig:  config,
		Environment: getEnvironment(ModeCompose),
	}

	_, err := nodeConfig.GetTopologyJson(&topology, nodes)

	assert.Error(t, err)
}

func getBridgesEmptyNodes(t *testing.T) {
	topology := getTestTopology()
	var nodes = make([]Node, 0)

	nodeConfig := NodeConfig{
		NodeConfig:  getNodeConfigs(),
		Environment: getEnvironment(ModeCompose),
	}

	_, err := nodeConfig.GetTopologyJson(&topology, nodes)

	assert.Error(t, err)
}

func getBridges(t *testing.T) {
	topologyJSON := []byte(`{"id":"5cc0474e4e9acc00282bb942","name":"test","nodes":[{"id":"5cc047dd4e9acc002a200c12","name":"start","worker":"worker.null","settings":{"url":"http://:0","actionPath":"","testPath":"","method":"","timeout":0},"followers":[{"id":"5cc047dd4e9acc002a200c14","name":"Xml_parser"}]},{"id":"5cc047dd4e9acc002a200c13","name":"Webhook","worker":"worker.http","settings":{"url":"http://monolith-api:80","actionPath":"/connector/Webhook/webhook","testPath":"/connector/Webhook/webhook/test","method":"POST","headers":{"customHeader":"customTail"},"timeout":0},"followers":[]},{"id":"5cc047dd4e9acc002a200c14","name":"Xml_parser","worker":"worker.http_xml_parser","settings":{"url":"http://xml-parser-api:80","actionPath":"/Xml_parser","testPath":"/Xml_parser/test","method":"POST", "headers":{"customHeader":"customTail"},"timeout":0},"followers":[{"id":"5cc047dd4e9acc002a200c13","name":"Webhook"}]}],"rabbitMq":[{"dsn":"amqp://rabbitmq:20/%2F"}]}`)

	topology := getTestTopology()
	nodes := getTestNodes()

	nodeConfig := NodeConfig{
		NodeConfig:  getNodeConfigs(),
		Environment: getEnvironment(ModeCompose),
	}

	result, err := nodeConfig.GetTopologyJson(&topology, nodes)
	if err != nil {
		t.Errorf("cannot parse topology json [%s]", err)
	}

	var expected TopologyJson
	err = json.Unmarshal(topologyJSON, &expected)

	assert.Equal(t, expected, result)
}

func getNodeConfigs() map[string]NodeUserParams {
	return map[string]NodeUserParams{
		"5cc047dd4e9acc002a200c12": {
			Worker: TopologyBridgeWorkerJSON{
				Type: "worker.null",
				Settings: TopologyBridgeWorkerSettingsJSON{
					PublishQueue: TopologyBridgeWorkerSettingsQueueJSON{},
				},
			},
		},
		"5cc047dd4e9acc002a200c13": {
			Faucet: TopologyBridgeFaucetSettingsJSON{
				Settings: map[string]int{
					"prefetch": 10,
				},
			},
			Worker: TopologyBridgeWorkerJSON{
				Type: "worker.http",
				Settings: TopologyBridgeWorkerSettingsJSON{
					Host:        "monolith-api",
					ProcessPath: "/connector/Webhook/webhook",
					StatusPath:  "/connector/Webhook/webhook/test",
					Method:      "POST",
					Headers: map[string]interface{}{
						"customHeader": "customTail",
					},
					Port:         80,
					Secure:       false,
					PublishQueue: TopologyBridgeWorkerSettingsQueueJSON{},
				},
			},
		},
		"5cc047dd4e9acc002a200c14": {
			Worker: TopologyBridgeWorkerJSON{
				Type: "worker.http_xml_parser",
				Settings: TopologyBridgeWorkerSettingsJSON{
					Host:        "xml-parser-api",
					ProcessPath: "/Xml_parser",
					StatusPath:  "/Xml_parser/test",
					Method:      "POST",
					Headers: map[string]interface{}{
						"customHeader": "customTail",
					},
					Port:         80,
					PublishQueue: TopologyBridgeWorkerSettingsQueueJSON{},
				},
			},
		},
	}
}

func getFullEnvironment(t *testing.T) {
	environment := getEnvironment(ModeCompose)

	result, err := environment.GetEnvironment()
	if err != nil {
		t.Errorf("unexpected error %s", err)
	}

	expected := map[string]string{
		"METRICS_DSN":    "influxdb://kapacitor:9100",
		"MONGODB_DSN":    "",
		"MONGODB_DB":     "",
		"UDP_LOGGER_URL": "",
	}

	assert.Equal(t, expected, result)
}

func getEnvironment(mode Adapter) Environment {
	return Environment{
		DockerRegistry:      "dkr.hanaboso.net/pipes/pipes",
		DockerPfBridgeImage: "hanaboso/bridge:dev",
		RabbitMqHost:        "rabbitmq:20",
		MetricsDsn:          "influxdb://kapacitor:9100",
		MetricsService:      "influx",
		WorkerDefaultPort:   8808,
		GeneratorMode:       mode,
	}
}

func getTestNodes() []Node {

	var nodes = make([]Node, 0)

	id, _ := primitive.ObjectIDFromHex("5cc047dd4e9acc002a200c12")
	var next = []NodeNext{
		{
			ID:   "5cc047dd4e9acc002a200c14",
			Name: "Xml_parser",
		},
	}

	nodes = append(nodes, Node{
		ID:       id,
		Name:     "start",
		Topology: "5cc0474e4e9acc00282bb942",
		Next:     next,
		Type:     "start",
		Handler:  "event",
		Enabled:  true,
		Deleted:  false,
	})

	id, _ = primitive.ObjectIDFromHex("5cc047dd4e9acc002a200c13")
	nodes = append(nodes, Node{
		ID:       id,
		Name:     "Webhook",
		Topology: "5cc0474e4e9acc00282bb942",
		Type:     "webhook",
		Handler:  "event",
		Enabled:  true,
		Deleted:  false,
	})

	id, _ = primitive.ObjectIDFromHex("5cc047dd4e9acc002a200c14")
	next = []NodeNext{
		{
			ID:   "5cc047dd4e9acc002a200c13",
			Name: "Webhook",
		},
	}

	nodes = append(nodes, Node{
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
