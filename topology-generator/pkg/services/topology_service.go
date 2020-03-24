package services

import (
	"encoding/json"
	"fmt"
	"github.com/sirupsen/logrus"
	"gopkg.in/yaml.v2"
	"strings"
	"topology-generator/pkg/config"
	"topology-generator/pkg/fs_commands"
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
	return json.Marshal(t)
}

func (ts *TopologyService) GenerateTopology() error {

	topologyJsonData, err := ts.CreateTopologyJson()
	if err != nil {
		return fmt.Errorf("error creating topology json. Reason: %v", err)
	}

	dstFile := GetDstDir(ts.generatorConfig.Path, ts.Topology.GetSaveDir())
	if err := fs_commands.WriteFile(
		dstFile,
		"topology.json",
		topologyJsonData,
	); err != nil {
		return fmt.Errorf("error writing to topology.json. Reason: %v", err)
	}

	logrus.Debugf("Save topology.json to %s", dstFile)

	return nil
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
	return yaml.Marshal(compose)
}

func (ts *TopologyService) CreateConfigMap() ([]byte, error) {
	data := make(map[string]string)
	topology, err := ts.CreateTopologyJson()

	if err != nil {
		return nil, fmt.Errorf("failed to create topology json. Reason: %v", err)
	}

	data["topology.json"] = string(topology)
	configMap := model.ConfigMap{
		ApiVersion: "v1",
		Kind:       "ConfigMap",
		Metadata: model.Metadata{
			Name: GetConfigMapName(ts.Topology.ID.Hex()),
		},
		Data: data,
	}

	return yaml.Marshal(configMap)
}

func (ts *TopologyService) CreateDeploymentService() ([]byte, error) {
	s := model.DeploymentService{
		ApiVersion: "v1",
		Kind:       "Service",
		Metadata: model.Metadata{
			Name: GetDeploymentName(ts.Topology.ID.Hex()),
		},
		Spec: model.ServiceSpec{
			Ports: []model.ServicePort{
				{
					Protocol: "TCP",
					Port:     ts.nodeConfig.Environment.WorkerDefaultPort,
				},
			},
			Selector: map[string]string{
				"app": GetDeploymentName(ts.Topology.ID.Hex()),
			},
		},
	}
	return yaml.Marshal(s)
}

func (ts *TopologyService) CreateKubernetesDeployment() ([]byte, error) {
	const mountName = "topologyjson"

	containers, err := ts.getKubernetesContainers(mountName)
	if err != nil {
		return nil, fmt.Errorf("error getting kubernetes containes. reason: %v", err)
	}

	labels := make(map[string]string)
	labels["app"] = ts.Topology.ID.Hex()
	var depl = model.Deployment{
		ApiVersion: "apps/v1",
		Kind:       "Deployment",
		Metadata: model.Metadata{
			Name: GetDeploymentName(ts.Topology.ID.Hex()),
		},
		Spec: model.Spec{
			Replicas: 0,
			Selector: model.Selector{MatchLabels: labels},
			Template: model.Template{
				Metadata: model.TemplateMetadata{
					Labels: labels,
				},
				Spec: model.TemplateSpec{
					Containers: containers,
					Volumes: []model.Volume{
						{
							Name: mountName,
							ConfigMap: model.ConfigMapVolume{
								Name: GetConfigMapName(ts.Topology.ID.Hex()),
							},
						},
					},
				},
			},
		},
	}
	return yaml.Marshal(depl)
}

func (ts *TopologyService) getKubernetesContainers(mountName string) ([]model.Container, error) {
	registry := ts.nodeConfig.Environment.DockerRegistry
	image := ts.nodeConfig.Environment.DockerPfBridgeImage
	topologyPath := ts.generatorConfig.TopologyPath
	multiNode := ts.generatorConfig.MultiNode

	environment, err := ts.nodeConfig.Environment.GetEnvironment()
	if err != nil {
		return nil, fmt.Errorf("error getting environment. reason: %v", err)
	}

	env := make([]model.EnvItem, len(environment))
	i := 0
	for name, value := range environment {
		env[i] = model.EnvItem{
			Name:  name,
			Value: value,
		}
		i++;
	}

	if multiNode {
		command := strings.Split(getMultiBridgeStartCommand(), " ")
		return []model.Container{
			{
				Name:    strings.ReplaceAll(ts.Topology.GetMultiNodeName(), "_", "-"),
				Command: []string{command[0]},
				Args:    command[1:],
				Image:   getDockerImage(registry, image),
				Ports: []model.Port{
					{
						ContainerPort: ts.nodeConfig.Environment.WorkerDefaultPort,
					},
				},
				Env: env,
				VolumeMounts: []model.VolumeMount{
					{
						Name:      mountName,
						MountPath: strings.ReplaceAll(topologyPath, "/topology.json", ""),
					},
				},
			},
		}, nil
	}
	containers := make([]model.Container, len(ts.Nodes))
	for i, node := range ts.Nodes {
		command := strings.Split(getSingleBridgeStartCommand(model.CreateServiceName(node.GetServiceName())), " ")
		containers[i] = model.Container{
			Name:    strings.ToLower(node.GetServiceName()),
			Command: []string{command[0]},
			Args:    command[1:],
			Image:   getDockerImage(registry, image),
			Ports: []model.Port{
				{
					ContainerPort: ts.nodeConfig.Environment.WorkerDefaultPort,
				},
			},
			VolumeMounts: []model.VolumeMount{
				{
					Name:      mountName,
					MountPath: strings.ReplaceAll(topologyPath, "/topology.json", ""),
				},
			},
		}
	}
	return containers, nil

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
	if err != nil {
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
			Command:     getMultiBridgeStartCommand(),
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
				Command:     getSingleBridgeStartCommand(model.CreateServiceName(node.GetServiceName())),
			}
		}
	}

	return services, nil
}

func NewTopologyService(nodeConfig model.NodeConfig, config config.GeneratorConfig, db StorageSvc, topologyId string) (*TopologyService, error) {
	topology, err := db.GetTopology(topologyId)

	if err != nil {
		return nil, fmt.Errorf("error getting topology %s. Reason: %v", topologyId, err)
	}

	nodes, err := db.GetTopologyNodes(topologyId)

	if err != nil {
		return nil, fmt.Errorf("error getting topology nodes %s. Reason: %v", topologyId, err)
	}

	return &TopologyService{
		Topology:        topology,
		Nodes:           nodes,
		nodeConfig:      nodeConfig,
		generatorConfig: config,
	}, nil
}
