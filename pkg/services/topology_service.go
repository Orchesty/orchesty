package services

import (
	"encoding/json"
	"fmt"
	v1 "k8s.io/api/core/v1"
	"strconv"
	"strings"

	log "github.com/hanaboso/go-log/pkg"
	"gopkg.in/yaml.v2"

	"topology-generator/pkg/config"
	"topology-generator/pkg/fscommands"
	"topology-generator/pkg/model"
)

type TopologyService struct {
	Topology        *model.Topology
	Nodes           []model.Node
	nodeConfig      model.NodeConfig
	generatorConfig config.GeneratorConfig
	logger          log.Logger
}

func (ts *TopologyService) CreateTopologyJSON() ([]byte, error) {
	topology, err := ts.nodeConfig.GetTopologyJson(ts.Topology, ts.Nodes)
	if err != nil {
		return nil, err
	}

	return json.Marshal(topology)
}

func (ts *TopologyService) GenerateTopology() error {
	topologyJSONData, err := ts.CreateTopologyJSON()
	if err != nil {
		return fmt.Errorf("error creating topology json. Reason: %v", err)
	}

	dstFile := GetDstDir(ts.generatorConfig.Path, ts.Topology.GetSaveDir())
	if err := fscommands.WriteFile(
		dstFile,
		"topology.json",
		topologyJSONData,
	); err != nil {
		return fmt.Errorf("error writing to topology.json. Reason: %v", err)
	}

	ts.logContext(nil).Debug("Save topology.json to %s", dstFile)

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

// CreateConfigMap CreateConfigMap
func (ts *TopologyService) CreateConfigMap() ([]byte, error) {
	data := make(map[string]string)
	topology, err := ts.CreateTopologyJSON()

	if err != nil {
		return nil, fmt.Errorf("failed to create topology json. Reason: %v", err)
	}

	data["topology.json"] = string(topology)
	configMap := model.ConfigMap{
		APIVersion: "v1",
		Kind:       "ConfigMap",
		Metadata: model.Metadata{
			Name: GetConfigMapName(ts.Topology.ID.Hex()),
		},
		Data: data,
	}

	return yaml.Marshal(configMap)
}

// CreateDeploymentService CreateDeploymentService
func (ts *TopologyService) CreateDeploymentService() ([]byte, error) {
	s := model.DeploymentService{
		APIVersion: "v1",
		Kind:       "Service",
		Metadata: model.Metadata{
			Name: ts.Topology.GetMultiNodeName(),
		},
		Spec: model.ServiceSpec{
			Ports: []model.ServicePort{
				{
					Protocol:   "TCP",
					Port:       8000,
					TargetPort: "http",
					Name:       "http",
				},
			},
			Selector: map[string]string{
				"app": GetDeploymentName(ts.Topology.ID.Hex()),
			},
		},
	}
	return yaml.Marshal(s)
}

// CreateKubernetesDeployment CreateKubernetesDeployment
func (ts *TopologyService) CreateKubernetesDeployment() ([]byte, error) {
	const mountName = "topologyjson"

	containers, err := ts.getKubernetesContainers(mountName)
	if err != nil {
		return nil, fmt.Errorf("error getting kubernetes containers. reason: %v", err)
	}

	labels := make(map[string]string)
	labels["app.kubernetes.io/instance"] = "pipes"
	for _, label := range strings.Split(config.Generator.TopologyPodLabels, ",") {
		value := strings.Split(label, "=")
		labels[value[0]] = value[1]
	}

	var depl = model.Deployment{
		APIVersion: "apps/v1",
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
		i++
	}

	limits := getResourceLimits(ts.nodeConfig.Environment.Limits, ts.generatorConfig.WorkerDefaultLimitMemory, ts.generatorConfig.WorkerDefaultLimitCPU)
	requests := getResourceRequests(ts.nodeConfig.Environment.Requests, ts.generatorConfig.WorkerDefaultRequestMemory, ts.generatorConfig.WorkerDefaultRequestCPU)

	if multiNode {
		command := strings.Split(getMultiBridgeStartCommand(), " ")
		return []model.Container{
			{
				Name:            ts.Topology.GetMultiNodeName(),
				Command:         []string{command[0]},
				Args:            command[1:],
				Image:           getDockerImage(registry, image),
				ImagePullPolicy: string(v1.PullAlways),
				Resources: model.Resources{
					Limits:   limits,
					Requests: requests,
				},
				Env: env,
				Ports: []model.Port{
					{
						Name:          "http",
						ContainerPort: ts.nodeConfig.Environment.WorkerDefaultPort,
					},
				},
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
			Name:            strings.ToLower(node.GetServiceName()),
			Command:         []string{command[0]},
			Args:            command[1:],
			Image:           getDockerImage(registry, image),
			ImagePullPolicy: string(v1.PullAlways),
			Resources: model.Resources{
				Limits:   limits,
				Requests: requests,
			},
			Ports: []model.Port{
				{
					Name:          "http",
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

	memory := ts.generatorConfig.WorkerDefaultLimitMemory
	if ts.nodeConfig.Environment.Limits.Memory != "" {
		memory = ts.nodeConfig.Environment.Limits.Memory
	}

	cpu := ts.generatorConfig.WorkerDefaultLimitCPU
	if ts.nodeConfig.Environment.Limits.CPU != "" {
		cpu = ts.nodeConfig.Environment.Limits.CPU
	}
	cpus, err := strconv.ParseFloat(cpu, 32)
	if err != nil {
		return nil, fmt.Errorf("failed parse cpu limit. reason: %v", err)
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
			MemLimit:    memory,
			Cpus:        cpus,
			Restart:     "on-failure",
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
				MemLimit:    memory,
				Cpus:        cpus,
				Restart:     "on-failure",
			}
		}
	}

	return services, nil
}

func (ts *TopologyService) logContext(data map[string]interface{}) log.Logger {
	if data == nil {
		data = make(map[string]interface{})
	}

	data["service"] = "topology-generator"
	data["type"] = "topology-service"

	return ts.logger.WithFields(data)
}

// NewTopologyService NewTopologyService
func NewTopologyService(nodeConfig model.NodeConfig, configuration config.GeneratorConfig, db StorageSvc, topologyID string) (*TopologyService, error) {
	topology, err := db.GetTopology(topologyID)

	if err != nil {
		return nil, fmt.Errorf("error getting topology %s. Reason: %v", topologyID, err)
	}

	nodes, err := db.GetTopologyNodes(topologyID)

	if err != nil {
		return nil, fmt.Errorf("error getting topology nodes %s. Reason: %v", topologyID, err)
	}

	return &TopologyService{
		Topology:        topology,
		Nodes:           nodes,
		nodeConfig:      nodeConfig,
		generatorConfig: configuration,
		logger:          config.Logger,
	}, nil
}
