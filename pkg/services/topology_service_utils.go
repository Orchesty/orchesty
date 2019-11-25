package services

import (
	"fmt"

	"topology-generator/pkg/model"
)

func getDockerGeneratorVersion(mode model.Adapter) string {
	if mode == model.ModeSwarm {
		return "3.3"
	} else {
		return "2"
	}
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

func GetConfigMapName(topologyId string) string {
	return fmt.Sprintf("configmap-%s", topologyId)
}

func GetDeploymentName(topologyId string) string {
	return fmt.Sprintf("topology-%s", topologyId)
}

func getPodName(topologyId string) string {
	return fmt.Sprintf("pod%s", topologyId)
}

func GetDstDir(path string, saveDir string) string {
	return fmt.Sprintf("%s/%s", path, saveDir)
}
