package services

import (
	"github.com/docker/docker/api/types"
	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
)

type mockDockerCli struct {
	mockGetDockerTopologyInfo func(status string, name string) ([]types.Container, error)
	mockGetSwarmTopologyInfo  func(status string, name string) ([]types.Container, error)
	mockCreateSwarmConfig     func(topology *model.Topology, generatorConfig config.GeneratorConfig) error
	mockRunSwarm              func(topology *model.Topology, generatorConfig config.GeneratorConfig) error
	mockStopSwarm             func(topology *model.Topology, prefix string) error
	mockStartCompose          func(dstDir string) error
	mockStopCompose           func(dstDir string) error
	mockRemoveSwarmConfig     func(topology *model.Topology, prefix string) error
	mockDockerClose           func() error
}

func (m mockDockerCli) GetDockerTopologyInfo(status string, name string) ([]types.Container, error) {
	return m.mockGetDockerTopologyInfo(status, name)
}

func (m mockDockerCli) GetSwarmTopologyInfo(status string, name string) ([]types.Container, error) {
	return m.mockGetSwarmTopologyInfo(status, name)
}

func (m mockDockerCli) CreateSwarmConfig(topology *model.Topology, generatorConfig config.GeneratorConfig) error {
	return m.mockCreateSwarmConfig(topology, generatorConfig)
}

func (m mockDockerCli) RunSwarm(topology *model.Topology, generatorConfig config.GeneratorConfig) error {
	return m.mockRunSwarm(topology, generatorConfig)
}

func (m mockDockerCli) StopSwarm(topology *model.Topology, prefix string) error {
	return m.mockStopSwarm(topology, prefix)
}

func (m mockDockerCli) StartCompose(dstDir string) error {
	return m.mockStartCompose(dstDir)
}

func (m mockDockerCli) StopCompose(dstDir string) error {
	return m.mockStopCompose(dstDir)
}

func (m mockDockerCli) RemoveSwarmConfig(topology *model.Topology, prefix string) error {
	return m.mockRemoveSwarmConfig(topology, prefix)
}

func (m mockDockerCli) Close() error {
	return m.mockDockerClose()
}

func getMockContainer() types.Container {
	return types.Container{
		ID:         "",
		Names:      nil,
		Image:      "",
		ImageID:    "",
		Command:    "",
		Created:    0,
		Ports:      nil,
		SizeRw:     0,
		SizeRootFs: 0,
		Labels:     nil,
		State:      "",
		Status:     "",
		HostConfig: struct {
			NetworkMode string `json:",omitempty"`
		}{},
		NetworkSettings: nil,
		Mounts:          nil,
	}
}
