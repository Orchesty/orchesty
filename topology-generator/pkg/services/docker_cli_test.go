package services

import (
	"github.com/stretchr/testify/require"
	"testing"
	"topology-generator/pkg/config"
)

func setupTestDockerSvc() {
}

func TestGetSwarmCreateConfigCmd(t *testing.T) {
	cmd, params := getSwarmCreateConfigCmd(getMockTopology(), config.GeneratorConfig{
		Path:              "/tmp",
		TopologyPath:      "/srv/app/topology/topology.json",
		ProjectSourcePath: "/",
		Mode:              "compose",
		ClusterConfig:     "",
		Namespace:         "",
		Prefix:            "",
		Network:           "demo_default",
		MultiNode:         true,
		WorkerDefaultPort: 0,
	})
	require.Equal(t, "docker", cmd, "Returned command should be docker")
	require.Equal(t, "config", params[0])
	require.Equal(t, "create", params[1])
	require.Equal(t, "/tmp/5dc0474e4e9acc00282bb942-TestTopology/topology.json", params[3])
}

func TestGetSwarmRunCmd(t *testing.T) {
	cmd, params := getSwarmRunCmd(getMockTopology(), config.GeneratorConfig{
		Path:              "/tmp",
		TopologyPath:      "/srv/app/topology/topology.json",
		ProjectSourcePath: "/",
		Mode:              "compose",
		ClusterConfig:     "",
		Namespace:         "",
		Prefix:            "",
		Network:           "demo_default",
		MultiNode:         true,
		WorkerDefaultPort: 0,
	})
	require.Equal(t, "docker", cmd, "Returned command should be docker")
	require.Equal(t, "stack", params[0])
	require.Equal(t, "deploy", params[1])
	require.Equal(t, "/tmp/5dc0474e4e9acc00282bb942-TestTopology/docker-compose.yml", params[4])
}

func TestGetSwarmStopCnd(t *testing.T) {
	cmd, params := getSwarmStopCmd(getMockTopology(), "")
	require.Equal(t, "docker", cmd, "Returned command should be docker")
	require.Equal(t, "stack", params[0])
	require.Equal(t, "rm", params[1])
}

func TestGetSwarmRmConfigCmd(t *testing.T) {
	cmd, params := getSwarmRmConfigCmd(getMockTopology(), "")
	require.Equal(t, "docker", cmd, "Returned command should be docker")
	require.Equal(t, "config", params[0])
	require.Equal(t, "rm", params[1])

}
