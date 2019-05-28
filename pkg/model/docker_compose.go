package model

type External struct {
	Name     string `yaml:"name,omitempty" json:"name,omitempty"`
	External bool   `yaml:"external" json:"external"`
}

type Configs struct {
	External bool `yaml:"external,omitempty" json:"external,omitempty"`
}

type ServiceNetworkConfig struct {
	Aliases     []string `yaml:"aliases,omitempty" json:"aliases,omitempty"`
	Ipv4Address string   `yaml:"ipv4_address,omitempty" json:"ipv4_address omitempty" mapstructure:"ipv4_address"`
	Ipv6Address string   `yaml:"ipv6_address,omitempty" json:"ipv6_address omitempty" mapstructure:"ipv6_address"`
}

type ServiceConfigs struct {
	Source string `yaml:"source,omitempty" json:"source,omitempty"`
	Target string `yaml:"target,omitempty" json:"target,omitempty"`
}

type NetworkConfig struct {
	Driver     string            `yaml:"driver,omitempty" json:"driver,omitempty"`
	DriverOpts map[string]string `yaml:"driver_opts,omitempty" json:"driver_opts,omitempty"`
	External   bool              `yaml:"external" json:"external"`
	Labels     map[string]string `yaml:"labels,omitempty" json:"labels,omitempty"`
}

type Service struct {
	Image       string                           `yaml:"image" json:"image"`
	WorkingDir  string                           `yaml:"working_dir,omitempty" json:"working_dir,omitempty"`
	User        string                           `yaml:"user,omitempty" json:"user,omitempty"`
	Environment map[string]string                `yaml:"environment,omitempty" json:"environment,omitempty"`
	Networks    map[string]*ServiceNetworkConfig `yaml:"networks,omitempty" json:"networks,omitempty"`
	Volumes     []string                         `yaml:"volumes,omitempty" json:"volumes,omitempty"`
	Configs     []ServiceConfigs                 `yaml:"configs,omitempty" json:"configs,omitempty"`
	Command     string                           `yaml:"command,omitempty" json:"command,omitempty"`
}

type DockerCompose struct {
	Version  string                    `yaml:"version" json:"version" default:"2"`
	Services map[string]*Service       `yaml:"services" json:"services"`
	Networks map[string]*NetworkConfig `yaml:"networks,omitempty" json:"networks,omitempty"`
	Configs  map[string]*Configs       `yaml:"configs,omitempty" json:"configs,omitempty"`
}
