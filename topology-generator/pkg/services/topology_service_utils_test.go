package services

import (
	"fmt"
	"github.com/stretchr/testify/require"
	"testing"

	"github.com/stretchr/testify/assert"

	"topology-generator/pkg/model"
)

func TestGetDockerTopologyInfo(t *testing.T) {
	t.Run("Test getting docker version base mode", func(t *testing.T) {
		tests := []struct {
			mode   model.Adapter
			result string
		}{
			{mode: model.ModeCompose, result: "2.4"},
			{mode: model.ModeSwarm, result: "3.3"},
		}
		var result string
		for _, test := range tests {
			result = getDockerGeneratorVersion(test.mode)
			assert.Equal(t, test.result, result)
		}
	})
}

func TestGetDockerNetworks(t *testing.T) {
	t.Run("Get default network config", func(t *testing.T) {
		expected := map[string]*model.NetworkConfig{
			"test": {
				External: true,
			},
		}

		result := getDockerNetworks(model.ModeCompose, "test")
		assert.Equal(t, expected, result)
	})
}

func TestGetDockerConfigs(t *testing.T) {
	t.Run("Get docker configs", func(t *testing.T) {
		topology := getTestTopology()

		t.Run("Get config for docker compose", func(t *testing.T) {
			expected := make(map[string]*model.Configs)
			result := getDockerConfigs(model.ModeCompose, "dev", &topology)
			assert.Equal(t, expected, result)
		})

		t.Run("Get config for swarm", func(t *testing.T) {
			expected := make(map[string]*model.Configs)
			expected["dev_4e9acc00282bb942_config"] = &model.Configs{
				External: true,
			}

			result := getDockerConfigs(model.ModeSwarm, "dev", &topology)
			assert.Equal(t, expected, result)
		})

	})
}

func TestGetDockerImage(t *testing.T) {
	t.Run("Get docker image name", func(t *testing.T) {
		expected := "registry_name/image_name"

		result := getDockerImage("registry_name", "image_name")
		assert.Equal(t, expected, result)
	})
}

func TestGetDockerServiceNetworks(t *testing.T) {
	t.Run("Get docker service networks", func(t *testing.T) {
		expected := map[string]*model.ServiceNetworkConfig{
			"test": {},
		}

		result := getDockerServiceNetworks("test")
		assert.Equal(t, expected, result)
	})
}

func TestGetDockerServiceConfigs(t *testing.T) {
	t.Run("Get docker service contacts", func(t *testing.T) {
		t.Run("Get config for swarm", func(t *testing.T) {
			result := getDockerServiceConfigs(model.ModeSwarm, "topology_path", "config_name")

			expected := []model.ServiceConfigs{
				{
					Source: "config_name",
					Target: "topology_path",
				},
			}

			assert.Equal(t, expected, result)
		})
		t.Run("Get config for compose", func(t *testing.T) {
			var expected []model.ServiceConfigs
			result := getDockerServiceConfigs(model.ModeCompose, "topology_path", "config_name")
			assert.Equal(t, expected, result)
		})
	})
}

func TestGetComposeCommand(t *testing.T) {
	t.Run("Get docker compose start command", func(t *testing.T) {
		assert.Equal(t, "/bin/bridge start", getMultiBridgeStartCommand())
	})
}

func TestGetSwarmCommand(t *testing.T) {
	t.Run("Get swarm start command", func(t *testing.T) {
		assert.Equal(t, "/bin/bridge start", getSingleBridgeStartCommand("test"))
	})
}

func TestGetConfigMapName(t *testing.T) {
	n := GetConfigMapName(topologyID)
	require.Equal(t, fmt.Sprintf("configmap-%s", topologyID), n)
}

func TestGetDeploymentName(t *testing.T) {
	n := GetDeploymentName(topologyID)
	require.Equal(t, fmt.Sprintf("topology-%s", topologyID), n)
}

func TestGetPodName(t *testing.T) {
	n := getPodName(topologyID)
	require.Equal(t, fmt.Sprintf("pod%s", topologyID), n)
}
