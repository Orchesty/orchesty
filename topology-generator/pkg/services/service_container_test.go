package services

import (
	"testing"
	"topology-generator/pkg/config"

	"github.com/stretchr/testify/require"
	"k8s.io/client-go/kubernetes"
)

func TestNewServiceContainer(t *testing.T) {
	dockerClient, err := DockerConnect()
	if err != nil {
		t.Fatal(err)
	}
	svc := NewServiceContainer(mockStorageSvc{}, dockerClient, nil, config.Generator)

	require.NotNil(t, svc)

	cfg := config.GeneratorConfig{
		Path:              "",
		TopologyPath:      "",
		ProjectSourcePath: "",
		Mode:              "k8s",
		ClusterConfig:     "",
		Namespace:         "",
		Prefix:            "",
		Network:           "",
		MultiNode:         false,
		BridgePort:        0,
	}
	clientset := kubernetes.Clientset{
		DiscoveryClient: nil,
	}
	svc = NewServiceContainer(mockStorageSvc{}, dockerClient, &clientset, cfg)

	require.NotNil(t, svc)
}
