package services

import (
	"fmt"
	"strconv"

	"topology-generator/pkg/model"
)

func getDockerGeneratorVersion(mode model.Adapter) string {
	if mode == model.ModeSwarm {
		return "3.3"
	}

	return "2.4"
}

func getDockerNetworks(adapter model.Adapter, network string) map[string]*model.NetworkConfig {
	var networks = make(map[string]*model.NetworkConfig)

	networks[network] = &model.NetworkConfig{
		External: true,
	}

	return networks
}

func getDockerConfigs(m model.Adapter, prefix string, t *model.Topology) map[string]*model.Configs {
	var configs = make(map[string]*model.Configs)

	if m == model.ModeSwarm {
		configs[t.GetConfigName(prefix)] = &model.Configs{
			External: true,
		}
	}

	return configs
}

func getDockerImage(registry string, image string) string {
	return fmt.Sprintf("%s/%s", registry, image)
}

func getDockerServiceNetworks(network string) map[string]*model.ServiceNetworkConfig {
	var networks = make(map[string]*model.ServiceNetworkConfig)

	networks[network] = &model.ServiceNetworkConfig{}

	return networks
}

func getDockerServiceConfigs(adapter model.Adapter, topologyPath string, configName string) []model.ServiceConfigs {
	var configs []model.ServiceConfigs

	if adapter == model.ModeSwarm {
		configs = append(configs,
			model.ServiceConfigs{
				Source: configName,
				Target: topologyPath,
			})
	}

	return configs
}

func getMultiBridgeStartCommand() string {
	return "./dist/src/bin/pipes.js start multi_bridge"
}

func getSingleBridgeStartCommand(serviceName string) string {
	return fmt.Sprintf("./dist/src/bin/pipes.js start bridge --id %s", serviceName)
}

// GetConfigMapName GetConfigMapName
func GetConfigMapName(topologyID string) string {
	return fmt.Sprintf("configmap-%s", topologyID)
}

// GetDeploymentName GetDeploymentName
func GetDeploymentName(topologyID string) string {
	return fmt.Sprintf("topology-%s", topologyID)
}

func getPodName(topologyID string) string {
	return fmt.Sprintf("pod%s", topologyID)
}

// GetDstDir GetDstDir
func GetDstDir(path string, saveDir string) string {
	return fmt.Sprintf("%s/%s", path, saveDir)
}

func getResourceLimits(limits model.Limits, defaultMemory, defaultCPU string) (model.ResourceLimits, error) {
	c := defaultCPU
	if limits.CPU != "" {
		c = limits.CPU
	}
	cpu, err := strconv.Atoi(c)
	if err != nil {
		return model.ResourceLimits{}, fmt.Errorf("failed get cpu limit. reason: %v", err)
	}

	memory := defaultMemory
	if limits.Memory != "" {
		memory = limits.Memory
	}

	return model.ResourceLimits{
		Memory: memory,
		CPU:    cpu,
	}, nil
}
