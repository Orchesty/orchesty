package services

import (
	"context"
	"fmt"
	"github.com/docker/docker/api/types"
	"github.com/docker/docker/api/types/filters"
	"github.com/docker/docker/client"
	"topology-generator/pkg/config"
	"topology-generator/pkg/fscommands"
	"topology-generator/pkg/model"
)

type dockercli struct {
	cli *client.Client
}

// GetDockerTopologyInfo GetDockerTopologyInfo
func (d dockercli) GetDockerTopologyInfo(status string, name string) ([]types.Container, error) {
	filterList := filters.NewArgs()

	filterList.Add("status", status)
	filterList.Add("label", fmt.Sprintf("com.docker.compose.project=%s", name))

	options := types.ContainerListOptions{
		Filters: filterList,
	}

	return d.cli.ContainerList(context.Background(), options)
}

// GetSwarmTopologyInfo GetSwarmTopologyInfo
func (d dockercli) GetSwarmTopologyInfo(status string, name string) ([]types.Container, error) {
	filterList := filters.NewArgs()
	filterList.Add("status", status)
	filterList.Add("label", fmt.Sprintf("com.docker.stack.namespace=%s", name))

	options := types.ContainerListOptions{
		Filters: filterList,
	}

	return d.cli.ContainerList(context.Background(), options)
}

// CreateSwarmConfig CreateSwarmConfig
func (d dockercli) CreateSwarmConfig(topology *model.Topology, generatorConfig config.GeneratorConfig) error {
	cmd, args := getSwarmCreateConfigCmd(topology, generatorConfig)
	_, stdErr, err := fscommands.Execute(cmd, args...)
	if err != nil {
		return fmt.Errorf("%s [%s]", err, stdErr.String())
	}
	return nil
}

// RunSwarm RunSwarm
func (d dockercli) RunSwarm(topology *model.Topology, generatorConfig config.GeneratorConfig) error {
	cmd, args := getSwarmRunCmd(topology, generatorConfig)
	_, stdErr, err := fscommands.Execute(cmd, args...)

	if err != nil {
		return fmt.Errorf("%s [%s]", err, stdErr.String())
	}
	return nil
}

// StopSwarm StopSwarm
func (d dockercli) StopSwarm(topology *model.Topology, prefix string) error {
	cmd, args := getSwarmRmConfigCmd(topology, prefix)
	_, stdErr, err := fscommands.Execute(cmd, args...)

	if err != nil {
		return fmt.Errorf("%s [%s]", err.Error(), stdErr.String())
	}
	return nil
}

// RemoveSwarmConfig RemoveSwarmConfig
func (d dockercli) RemoveSwarmConfig(topology *model.Topology, prefix string) error {
	cmd, args := getSwarmStopCmd(topology, prefix)
	_, stdErr, err := fscommands.Execute(cmd, args...)
	if err != nil {
		return fmt.Errorf("%s [%s]", err.Error(), stdErr.String())
	}
	return nil
}

// StartCompose StartCompose
func (d dockercli) StartCompose(dstDir string) error {
	configPath := getDockerComposePath(dstDir)
	_, stdErr, err := fscommands.Execute("docker-compose", "-f", configPath, "up", "-d")
	if err != nil {
		return fmt.Errorf("%s [%s]", err.Error(), stdErr.String())
	}
	return nil
}

// StopCompose StopCompose
func (d dockercli) StopCompose(dstDir string) error {
	configPath := getDockerComposePath(dstDir)
	_, stdErr, err := fscommands.Execute("docker-compose", "-f", configPath, "down")

	if err != nil {
		return fmt.Errorf("%s [%s]", err.Error(), stdErr.String())
	}
	return nil
}

func (d *dockercli) Close() error {
	if d.cli != nil {
		return d.cli.Close()
	}
	return nil
}

func getDockerComposePath(dstDir string) string {
	configPath := fmt.Sprintf("%s/docker-compose.yml", dstDir)
	return configPath
}

// DockerCliSvc DockerCliSvc
type DockerCliSvc interface {
	GetDockerTopologyInfo(status string, name string) ([]types.Container, error)
	GetSwarmTopologyInfo(status string, name string) ([]types.Container, error)
	CreateSwarmConfig(topology *model.Topology, generatorConfig config.GeneratorConfig) error
	RunSwarm(topology *model.Topology, generatorConfig config.GeneratorConfig) error
	StopSwarm(topology *model.Topology, prefix string) error
	StartCompose(dstDir string) error
	StopCompose(dstDir string) error
	RemoveSwarmConfig(topology *model.Topology, prefix string) error
	Close() error
}

// NewDockerCliSvc NewDockerCliSvc
func NewDockerCliSvc(cli *client.Client) DockerCliSvc {
	return &dockercli{cli: cli}
}

// DockerConnect DockerConnect
func DockerConnect() (*client.Client, error) {
	cli, err := client.NewEnvClient()

	if err != nil {
		return nil, err
	}

	return cli, nil
}

func getSwarmCreateConfigCmd(topology *model.Topology, generatorConfig config.GeneratorConfig) (string, []string) {
	topologyJSON := fmt.Sprintf("%s/%s/topology.json", generatorConfig.Path, topology.GetSaveDir())
	return "docker", []string{
		"config",
		"create",
		topology.GetConfigName(generatorConfig.Prefix),
		topologyJSON,
	}
}

func getSwarmRunCmd(topology *model.Topology, generatorConfig config.GeneratorConfig) (string, []string) {
	dstDir := fmt.Sprintf("%s/%s", generatorConfig.Path, topology.GetSaveDir())
	configPath := fmt.Sprintf("%s/docker-compose.yml", dstDir)

	return "docker", []string{
		"stack",
		"deploy",
		"--with-registry-auth",
		"-c",
		configPath,
		topology.GetTopologyPrefix(generatorConfig.Prefix),
	}
}

func getSwarmStopCmd(topology *model.Topology, prefix string) (string, []string) {
	return "docker", []string{"stack", "rm", topology.GetTopologyPrefix(prefix)}
}

func getSwarmRmConfigCmd(topology *model.Topology, prefix string) (string, []string) {
	return "docker", []string{"config", "rm", topology.GetConfigName(prefix)}
}
