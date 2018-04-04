package docker_compose

import (
	"fmt"

	"hanaboso/topologygenerator/generator"
	"hanaboso/utils/servicename"
	str "hanaboso/utils/strings"
	"hanaboso/utils/topology"

	"github.com/spf13/viper"
	"gopkg.in/yaml.v2"
)

func Create(te *topology.Topology, nodes []topology.Node, mode string) ([]byte, error) {

	var (
		services map[string]*Service
		networks map[string]*NetworkConfig
		configs  map[string]*Configs
		compose  DockerCompose
	)

	services = getServices(te, nodes, mode)
	networks = getNetworks(mode)
	configs = getConfigs(mode, te)

	compose = DockerCompose{
		Version:  getGeneratorVersion(viper.GetString("generator.mode")),
		Services: services,
		Networks: networks,
		Configs:  configs,
	}
	out, err := yaml.Marshal(compose)

	return out, err
}

func getGeneratorVersion(mode string) string {
	if mode == generator.MODESWARM {
		return "3.3"
	} else {
		return "2"
	}
}

func GetConfigName(te *topology.Topology) string {
	return fmt.Sprintf(
		"%s_%s_config",
		viper.GetString("generator.topology-prefix"),
		str.Substring(te.ID.Hex(), 8, len(te.ID.Hex())),
	)
}

func GetTopologyPrefix(te *topology.Topology) string {
	return fmt.Sprintf(
		"%s_%s",
		viper.GetString("generator.topology-prefix"),
		str.Substring(te.ID.Hex(), 8, len(te.ID.Hex())),
	)
}

func getServices(te *topology.Topology, nodes []topology.Node, mode string) map[string]*Service {
	var services = make(map[string]*Service)

	////Add Probe
	//services[te.GetProbeServiceName()] = &Service{
	//	Image:         getImage(),
	//	Environment:   getEnvironment(),
	//	Networks:      getServiceNetworks(),
	//	Volumes:       getVolumes(mode, te),
	//	Configs:       getServiceConfigs(mode, te),
	//	Command:       "./dist/src/bin/pipes.js start probe",
	//}
	//
	////Add Counter
	//services[te.GetCounterServiceName()] = &Service{
	//	Image:         getImage(),
	//	Environment:   getEnvironment(),
	//	Networks:      getServiceNetworks(),
	//	Volumes:       getVolumes(mode, te),
	//	Configs:       getServiceConfigs(mode, te),
	//	Command:       "./dist/src/bin/pipes.js start counter",
	//}

	// Add bridges
	if viper.GetBool("generator.multimode") {
		services[te.GetMultiServiceName()] = &Service{
			Image:       getImage(),
			Environment: getEnvironment(),
			Networks:    getServiceNetworks(),
			Volumes:     getVolumes(mode, te),
			Configs:     getServiceConfigs(mode, te),
			Command:     "./dist/src/bin/pipes.js start multi_bridge",
		}
	} else {
		var node topology.Node
		for _, node = range nodes {
			services[node.GetServiceName()] = &Service{
				Image:       getImage(),
				Environment: getEnvironment(),
				Networks:    getServiceNetworks(),
				Volumes:     getVolumes(mode, te),
				Configs:     getServiceConfigs(mode, te),
				Command:     getCommand(servicename.CreateServiceName(node.GetServiceName())),
			}
		}
	}

	return services
}

func getCommand(serviceName string) string {
	return fmt.Sprintf("./dist/src/bin/pipes.js start node --id %s", serviceName)
}

func getImage() string {
	return fmt.Sprintf(
		"%s/%s",
		viper.GetString("environment.docker-registry"),
		viper.GetString("environment.docker-pf-bridge-image"),
	)
}

func getConfigs(m string, te *topology.Topology) map[string]*Configs {
	var configs = make(map[string]*Configs)

	if m == generator.MODESWARM {
		configs[GetConfigName(te)] = &Configs{
			External: true,
		}
	}

	return configs
}

func getServiceConfigs(m string, te *topology.Topology) []ServiceConfigs {
	var configs []ServiceConfigs

	if m == generator.MODESWARM {
		configs = append(configs,
			ServiceConfigs{
				Source: GetConfigName(te),
				Target: viper.GetString("generator.topology-json-path"),
			})
	}

	return configs
}

func getVolumes(m string, topology *topology.Topology) []string {
	var volumes []string

	if m == generator.MODECOMPOSE {
		volumes = append(volumes, fmt.Sprintf(
			"%s/%s/topology.json:%s",
			viper.GetString("generator.project-path"),
			topology.GetSaveDir(),
			viper.GetString("generator.topology-json-path")),
		)
	}

	return volumes
}

func getServiceNetworks() map[string]*ServiceNetworkConfig {
	var networks = make(map[string]*ServiceNetworkConfig)

	networks[viper.GetString("generator.network_name")] = &ServiceNetworkConfig{}

	return networks
}

func getNetworks(m string) map[string]*NetworkConfig {
	var networks = make(map[string]*NetworkConfig)

	networks[viper.GetString("generator.network_name")] = &NetworkConfig{
		External: true,
	}

	return networks
}

func getEnvironment() map[string]string {
	var environment = make(map[string]string)

	environment[RABBITMQ_HOST] = viper.GetString("environment.rabbitmq-host")
	environment[RABBITMQ_PORT] = viper.GetString("environment.rabbitmq-port")
	environment[RABBITMQ_USER] = viper.GetString("environment.rabbitmq-user")
	environment[RABBITMQ_PASS] = viper.GetString("environment.rabbitmq-pass")
	environment[RABBITMQ_VHOST] = viper.GetString("environment.rabbitmq-vhost")
	environment[MULTI_PROBE_HOST] = viper.GetString("environment.multi-probe-host")
	environment[MULTI_PROBE_PORT] = viper.GetString("environment.multi-probe-port")
	environment[METRICS_HOST] = viper.GetString("environment.metrics-host")
	environment[METRICS_PORT] = viper.GetString("environment.metrics-port")

	return environment
}
