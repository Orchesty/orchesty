package services

import (
	"fmt"
	"github.com/stretchr/testify/require"
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
		"5cc047dd4e9acc002a200c12-start": {
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

	ts, err := NewTopologyService(nodeConfig, generatorConfig, nil, "")
	if err != nil {
		t.Fatal(err)
	}
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
		RabbitMqDsn:         "rabbitmq:5672",
		MultiProbeHost:      "multi-probe:8007",
		MetricsHost:         "kapacitor:9100",
		WorkerDefaultPort:   8808,
		GeneratorMode:       mode,
	}
}

func TestTopologyService_CreateTopologyJsonFails(t *testing.T) {
	nodeConfig := model.NodeConfig{
		NodeConfig: getNodeConfigs(),
		Environment: model.Environment{
			DockerRegistry:      "testregistry",
			DockerPfBridgeImage: "testimages",
			RabbitMqDsn:         "",
			MultiProbeHost:      "",
			MetricsHost:         "",
			MetricsPort:         "",
			MetricsService:      "",
			WorkerDefaultPort:   8888,
			GeneratorMode:       "",
		},
	}

	// check that creating topology fails
	ts, err := NewTopologyService(nodeConfig, testConfigGenerator, testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return getMockTopology(), nil
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return []model.Node{}, nil
		},
	}, "topologyId")
	if err != nil {
		t.Fatal(err)
	}
	err = ts.GenerateTopology()
	require.NotNil(t, err)

	errorConfigGenerator := config.GeneratorConfig{
		Path:              "/non/existing",
		TopologyPath:      "/srv/app/topology/topology.json",
		ProjectSourcePath: "/",
		Mode:              "compose",
		ClusterConfig:     "",
		Namespace:         "",
		Prefix:            "",
		Network:           "demo_default",
		MultiNode:         true,
		WorkerDefaultPort: 0,
	}

	// check that writing topology.json fails
	ts, err = NewTopologyService(nodeConfig, errorConfigGenerator, testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return getMockTopology(), nil
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return getTestNodes(), nil
		},
	}, "topologyId")
	if err != nil {
		t.Fatal(err)
	}
	err = ts.GenerateTopology()
	require.NotNil(t, err)

}

func TestNewTopologyServiceFails(t *testing.T) {
	// check that NewTopologyService returns errors
	ts, err := NewTopologyService(model.NodeConfig{}, testConfigGenerator, testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return nil, fmt.Errorf("test error")
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return []model.Node{}, nil
		},
	}, "unknownTopologyId")
	require.NotNil(t, err)
	require.Nil(t, ts)

	// check that NewTopologyService returns errors
	ts, err = NewTopologyService(model.NodeConfig{}, testConfigGenerator, testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return getMockTopology(), nil
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return []model.Node{}, fmt.Errorf("test error")
		},
	}, "unknownTopologyId")
	require.NotNil(t, err)
	require.Nil(t, ts)
}

func TestGetDockerServicesFails(t *testing.T) {
	// check that NewTopologyService returns errors
	ts, err := NewTopologyService(model.NodeConfig{
		NodeConfig: nil,
		Environment: model.Environment{
			DockerRegistry:      "",
			DockerPfBridgeImage: "",
			RabbitMqDsn:         "[x:",
			MultiProbeHost:      "[y:",
			MetricsHost:         "",
			MetricsPort:         "",
			MetricsService:      "",
			WorkerDefaultPort:   0,
			GeneratorMode:       "",
		},
	}, testConfigGenerator, testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return getMockTopology(), nil
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return []model.Node{}, nil
		},
	}, topologyId)

	if err != nil {
		t.Fatal(err)
	}
	_, err = ts.getDockerServices(model.ModeCompose)
	require.NotNil(t, err)
}

func TestTopologyService_CreateDockerComposeFails(t *testing.T) {
	// check that NewTopologyService returns errors
	ts, err := NewTopologyService(model.NodeConfig{
		NodeConfig: nil,
		Environment: model.Environment{
			DockerRegistry:      "",
			DockerPfBridgeImage: "",
			RabbitMqDsn:         "[x:",
			MultiProbeHost:      "[y:",
			MetricsHost:         "",
			MetricsPort:         "",
			MetricsService:      "",
			WorkerDefaultPort:   0,
			GeneratorMode:       "",
		},
	}, testConfigGenerator, testDb{
		mockGetTopology: func(id string) (topology *model.Topology, e error) {
			return getMockTopology(), nil
		},
		mockGetTopologyNodes: func(id string) (nodes []model.Node, e error) {
			return []model.Node{}, nil
		},
	}, topologyId)

	if err != nil {
		t.Fatal(err)
	}
	_, err = ts.CreateDockerCompose(model.ModeCompose)
	require.NotNil(t, err)
}
