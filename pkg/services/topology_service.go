package services

import (
	"encoding/json"

	"gopkg.in/yaml.v2"

	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
)

type TopologyService struct {
	Topology        *model.Topology
	Nodes           []model.Node
	nodeConfig      model.NodeConfig
	generatorConfig config.GeneratorConfig
}

func (ts *TopologyService) CreateTopologyJson() ([]byte, error) {
	var bridges, err = ts.nodeConfig.GetBridges(ts.Topology, ts.Nodes, ts.nodeConfig.Environment.WorkerDefaultPort)

	if err != nil {
		return nil, err
	}

	t := model.TopologyJson{
		ID:           model.CreateServiceName(ts.Topology.NormalizeName()),
		TopologyName: ts.Topology.Name,
		TopologyId:   ts.Topology.ID.Hex(),
		Bridges:      bridges,
	}
	bytes, err := json.Marshal(t)

	return bytes, err
}

func (ts *TopologyService) CreateDockerCompose(adapter model.Adapter) ([]byte, error) {
	var (
		services map[string]*model.Service
		networks map[string]*model.NetworkConfig
		configs  map[string]*model.Configs
		compose  model.DockerCompose
	)

	services, err := ts.getDockerServices(adapter)
	if err != nil {
		return nil, err
	}

	networks = getDockerNetworks(adapter, ts.generatorConfig.Network)
	configs = getDockerConfigs(adapter, ts.generatorConfig.Prefix, ts.Topology)

	compose = model.DockerCompose{
		Version:  getDockerGeneratorVersion(adapter),
		Services: services,
		Networks: networks,
		Configs:  configs,
	}
	out, err := yaml.Marshal(compose)

	return out, err
}

func (ts *TopologyService) getDockerServices(mode model.Adapter) (map[string]*model.Service, error) {
	var services = make(map[string]*model.Service)

	prefix := ts.generatorConfig.Prefix
	registry := ts.nodeConfig.Environment.DockerRegistry
	image := ts.nodeConfig.Environment.DockerPfBridgeImage
	configName := ts.Topology.GetConfigName(prefix)

	network := ts.generatorConfig.Network
	topologyPath := ts.generatorConfig.TopologyPath
	projectPath := ts.generatorConfig.ProjectSourcePath
	multiNode := ts.generatorConfig.MultiNode

	environment, err := ts.nodeConfig.Environment.GetEnvironment()
	if err != err {
		return nil, err
	}

	// Add bridges
	if multiNode {
		services[ts.Topology.GetMultiNodeName()] = &model.Service{
			Image:       getDockerImage(registry, image),
			Environment: environment,
			Networks:    getDockerServiceNetworks(network),
			Volumes:     ts.Topology.GetVolumes(mode, projectPath, topologyPath),
			Configs:     getDockerServiceConfigs(mode, topologyPath, configName),
			Command:     getComposeCommand(),
		}
	} else {
		var node model.Node
		for _, node = range ts.Nodes {
			services[node.GetServiceName()] = &model.Service{
				Image:       getDockerImage(registry, image),
				Environment: environment,
				Networks:    getDockerServiceNetworks(network),
				Volumes:     ts.Topology.GetVolumes(mode, projectPath, topologyPath),
				Configs:     getDockerServiceConfigs(mode, topologyPath, configName),
				Command:     getSwarmCommand(model.CreateServiceName(node.GetServiceName())),
			}
		}
	}

	return services, nil
}

func NewTopologyService(topology *model.Topology, nodes []model.Node, nodeConfig model.NodeConfig, config config.GeneratorConfig) *TopologyService {
	return &TopologyService{
		Topology:        topology,
		Nodes:           nodes,
		nodeConfig:      nodeConfig,
		generatorConfig: config,
	}
}
