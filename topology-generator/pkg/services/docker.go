package services

import (
	"fmt"
	"github.com/docker/docker/api/types"
	"github.com/sirupsen/logrus"
	"topology-generator/pkg/config"
	"topology-generator/pkg/fs_commands"
)

type dockerClient struct{}

func (d dockerClient) RunStop(topologyId string, db StorageSvc, dockerCli DockerCliSvc, generatorConfig config.GeneratorConfig, action string) ([]types.Container, error) {
	topology, err := db.GetTopology(topologyId)

	if err != nil {
		return nil, fmt.Errorf("getting topology %s failed. Reason: %v", topologyId, err)
	}
	dstDir := GetDstDir(generatorConfig.Path, topology.GetSaveDir())

	switch action {
	case "start":
		err = dockerCli.StartCompose(dstDir)
		if err != nil {
			return nil, fmt.Errorf("error starting dockerCli compose. Reason: %v", err)
		}
		containers, err := dockerCli.GetDockerTopologyInfo("running", topology.GetDockerName())
		if err != nil {
			return nil, fmt.Errorf("error getting running containers, Reason: %v", err)
		}
		return containers, nil
	case "stop":
		err = dockerCli.StopCompose(dstDir)
		if err != nil {
			return nil, fmt.Errorf("error stopping dockerCli composer. Reason: %v", err)
		}
		return nil, nil
	}

	return nil, fmt.Errorf("action %s not allow", action)
}

func (d dockerClient) Generate(ts *TopologyService) error {

	dstFile := GetDstDir(ts.generatorConfig.Path, ts.Topology.GetSaveDir())
	err := ts.GenerateTopology()
	if err != nil {
		return fmt.Errorf("error generating topology. Reason: %v", err)
	}
	dockerCompose, err := ts.CreateDockerCompose(ts.generatorConfig.Mode)
	if err != nil {
		return fmt.Errorf("writing docker-compose[topology_id=%s] failed. Reason: %v", ts.Topology.ID.Hex(), err)
	}

	if err := fs_commands.WriteFile(
		dstFile,
		"docker-compose.yml",
		dockerCompose,
	); err != nil {
		return fmt.Errorf("writing topology[id=%s, file=docker-compose.json] failed. Reason: %v", ts.Topology.ID.Hex(), err)
	}

	logrus.Debugf("Save docker-compose.yml to %s", dstFile)

	return nil
}

func (d dockerClient) RunStopSwarm(topologyId string, db StorageSvc, dockerCli DockerCliSvc, generatorConfig config.GeneratorConfig, action string) ([]types.Container, error) {
	topology, err := db.GetTopology(topologyId)

	if err != nil {
		return nil, fmt.Errorf("getting topology %s failed. Reason: %v", topologyId, err)
	}
	switch action {
	case "start":
		err = dockerCli.CreateSwarmConfig(topology, generatorConfig)
		if err != nil {
			return nil, fmt.Errorf("failed to create swarm config, Reason: %v", err)
		}

		err = dockerCli.RunSwarm(topology, generatorConfig)
		if err != nil {
			return nil, fmt.Errorf("failed to run swarm. Reason: %v", err)
		}

		containers, err := dockerCli.GetSwarmTopologyInfo("running", topology.GetSwarmName(generatorConfig.Prefix))
		if err != nil {
			return nil, fmt.Errorf("Error getting running containers, Reason: %v", err)
		}
		return containers, nil
	case "stop":
		err = dockerCli.RemoveSwarmConfig(topology, generatorConfig.Prefix)
		if err != nil {
			return nil, fmt.Errorf("failed to remove swarm config. Reason: %v", err)
		}

		err = dockerCli.StopSwarm(topology, generatorConfig.Prefix)
		if err != nil {
			return nil, fmt.Errorf("failed to stop swarm. Reason: %v", err)
		}

		return nil, nil
	}

	return nil, fmt.Errorf("action %s not allow", action)

}

func (d dockerClient) DeleteSwarm(topologyId string, db StorageSvc, dockerCli DockerCliSvc, generatorConfig config.GeneratorConfig) error {
	topology, err := db.GetTopology(topologyId)

	if err != nil {
		return fmt.Errorf("getting topology %s failed. Reason: %v", topologyId, err)
	}

	err = dockerCli.StopSwarm(topology, generatorConfig.Prefix)

	if err != nil {
		return fmt.Errorf("failed to stop swarm. Reason: %v", err)
	}

	dstDir := GetDstDir(generatorConfig.Path, topology.GetSaveDir())
	err = fs_commands.RemoveDirectory(dstDir)
	if err != nil {
		return fmt.Errorf("error removing %s. Reason: %v", dstDir, err)
	}
	return nil
}

func (d dockerClient) Delete(topologyId string, db StorageSvc, generatorConfig config.GeneratorConfig) error {
	topology, err := db.GetTopology(topologyId)

	if err != nil {
		return fmt.Errorf("failed to get topology. Reason: %v", err)
	}

	dstDir := fmt.Sprintf("%s/%s", generatorConfig.Path, topology.GetSaveDir())
	err = fs_commands.RemoveDirectory(dstDir)

	if err != nil {
		return fmt.Errorf("failed to remove %s. Reason: %v", dstDir, err)
	}
	return nil
}

type DockerSvc interface {
	Delete(topologyId string, db StorageSvc, generatorConfig config.GeneratorConfig) error
	DeleteSwarm(topologyId string, db StorageSvc, dockerCli DockerCliSvc, generatorConfig config.GeneratorConfig) error
	RunStopSwarm(topologyId string, db StorageSvc, dockerCli DockerCliSvc, generatorConfig config.GeneratorConfig, action string) ([]types.Container, error)
	RunStop(topologyId string, db StorageSvc, dockerCli DockerCliSvc, generatorConfig config.GeneratorConfig, action string) ([]types.Container, error)
	Generate(ts *TopologyService) error
}

func NewDockerSvc() DockerSvc {
	return &dockerClient{}
}
