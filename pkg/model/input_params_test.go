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

	result, err := nodeConfig.GetBridges(&topology, nodes, 8088)

	assert.Error(t, err)
	assert.Nil(t, result)
}

func getBridgesEmptyNodes(t *testing.T) {
	topology := getTestTopology()
	var nodes = make([]Node, 0)

	nodeConfig := NodeConfig{
		NodeConfig:  getNodeConfigs(),
		Environment: getEnvironment(ModeCompose),
	}

	result, err := nodeConfig.GetBridges(&topology, nodes, 8088)

	assert.Error(t, err)
	assert.Nil(t, result)
}

func getBridges(t *testing.T) {
	topologyJson := []byte(`[{"id":"5cc047dd4e9acc002a200c12-sta","label":{"id":"5cc047dd4e9acc002a200c12-sta","node_id":"5cc047dd4e9acc002a200c12","node_name":"start"},"faucet":{},"worker":{"type":"worker.null","settings":{"publish_queue":{}}},"next":["5cc047dd4e9acc002a200c14-xml"],"debug":{"port":8088,"host":"5cc0474e4e9acc00282bb942_mb","url":"http://5cc0474e4e9acc00282bb942_mb:8088/status"}},{"id":"5cc047dd4e9acc002a200c13-web","label":{"id":"5cc047dd4e9acc002a200c13-web","node_id":"5cc047dd4e9acc002a200c13","node_name":"Webhook"},"faucet":{"settings":{"prefetch":10}},"worker":{"type":"worker.http","settings":{"host":"monolith-api","process_path":"/connector/Webhook/webhook","status_path":"/connector/Webhook/webhook/test","method":"POST","port":80,"publish_queue":{}}},"next":[],"debug":{"port":8089,"host":"5cc0474e4e9acc00282bb942_mb","url":"http://5cc0474e4e9acc00282bb942_mb:8089/status"}},{"id":"5cc047dd4e9acc002a200c14-xml","label":{"id":"5cc047dd4e9acc002a200c14-xml","node_id":"5cc047dd4e9acc002a200c14","node_name":"Xml_parser"},"faucet":{},"worker":{"type":"worker.http_xml_parser","settings":{"host":"xml-parser-api","process_path":"/Xml_parser","status_path":"/Xml_parser/test","method":"POST","port":80,"publish_queue":{}}},"next":["5cc047dd4e9acc002a200c13-web"],"debug":{"port":8090,"host":"5cc0474e4e9acc00282bb942_mb","url":"http://5cc0474e4e9acc00282bb942_mb:8090/status"}}]`)

	topology := getTestTopology()
	nodes := getTestNodes()

	nodeConfig := NodeConfig{
		NodeConfig:  getNodeConfigs(),
		Environment: getEnvironment(ModeCompose),
	}

	result, err := nodeConfig.GetBridges(&topology, nodes, 8088)
	if err != nil {
		t.Errorf("cannot get bridges [%s]", err)
	}

	var expected []TopologyBridgeJson
	err = json.Unmarshal(topologyJson, &expected)

	assert.Equal(t, expected, result)
}

func getNodeConfigs() map[string]NodeUserParams {
	return map[string]NodeUserParams{
		"5cc047dd4e9acc002a200c12": {
			Worker: TopologyBridgeWorkerJson{
				Type: "worker.null",
				Settings: TopologyBridgeWorkerSettingsJson{
					PublishQueue: TopologyBridgeWorkerSettingsQueueJson{},
				},
			},
		},
		"5cc047dd4e9acc002a200c13": {
			Faucet: TopologyBridgeFaucetSettingsJson{
				Settings: map[string]int{
					"prefetch": 10,
				},
			},
			Worker: TopologyBridgeWorkerJson{
				Type: "worker.http",
				Settings: TopologyBridgeWorkerSettingsJson{
					Host:         "monolith-api",
					ProcessPath:  "/connector/Webhook/webhook",
					StatusPath:   "/connector/Webhook/webhook/test",
					Method:       "POST",
					Port:         80,
					Secure:       false,
					PublishQueue: TopologyBridgeWorkerSettingsQueueJson{},
				},
			},
		},
		"5cc047dd4e9acc002a200c14": {
			Worker: TopologyBridgeWorkerJson{
				Type: "worker.http_xml_parser",
				Settings: TopologyBridgeWorkerSettingsJson{
					Host:         "xml-parser-api",
					ProcessPath:  "/Xml_parser",
					StatusPath:   "/Xml_parser/test",
					Method:       "POST",
					Port:         80,
					PublishQueue: TopologyBridgeWorkerSettingsQueueJson{},
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
		"METRICS_HOST":     "kapacitor",
		"METRICS_PORT":     "9100",
		"METRICS_SERVICE":  "influx",
		"MULTI_PROBE_HOST": "multi-probe",
		"MULTI_PROBE_PORT": "8007",
		"RABBITMQ_HOST":    "rabbitmq",
		"RABBITMQ_PORT":    "20",
		"RABBITMQ_PASS":    "",
		"RABBITMQ_USER":    "",
		"RABBITMQ_VHOST":   "",
	}

	assert.Equal(t, expected, result)

}

func getEnvironment(mode Adapter) Environment {
	return Environment{
		DockerRegistry:      "dkr.hanaboso.net/pipes/pipes",
		DockerPfBridgeImage: "pf-bridge:dev",
		RabbitMqHost:        "rabbitmq:20",
		MultiProbeHost:      "multi-probe:8007",
		MetricsHost:         "kapacitor",
		MetricsPort:         "9100",
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
