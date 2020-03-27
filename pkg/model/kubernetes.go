package model

type Metadata struct {
	Name string
}

type Selector struct {
	MatchLabels map[string]string `yaml:"matchLabels"`
}

type TemplateMetadata struct {
	Labels map[string]string
}

type Port struct {
	Name          string
	ContainerPort int `yaml:"containerPort"`
}

type VolumeMount struct {
	Name      string
	MountPath string `yaml:"mountPath"`
}

type EnvItem struct {
	Name  string
	Value string
}

type Container struct {
	Name         string
	Image        string
	Command      []string
	Args         []string
	Ports        []Port
	Env          []EnvItem
	VolumeMounts []VolumeMount `yaml:"volumeMounts"`
}

type ConfigMapVolume struct {
	Name string
}

type Volume struct {
	Name      string
	ConfigMap ConfigMapVolume `yaml:"configMap"`
}

type TemplateSpec struct {
	Containers         []Container
	Volumes            []Volume
	ServiceAccountName string `yaml:"serviceAccountName"`
}

type Template struct {
	Metadata TemplateMetadata
	Spec     TemplateSpec
}

type Spec struct {
	Replicas int
	Selector Selector
	Template Template
}

type Deployment struct {
	ApiVersion string `yaml:"apiVersion"`
	Kind       string
	Metadata   Metadata
	Spec       Spec
}

type ConfigMap struct {
	ApiVersion string `yaml:"apiVersion"`
	Kind       string
	Metadata   Metadata
	Data       map[string]string
}

type ServiceSpec struct {
	Selector map[string]string
	Ports    []ServicePort
}

type ServicePort struct {
	Protocol   string
	Port       int
	TargetPort string `yaml:"targetPort"`
}

type DeploymentService struct {
	ApiVersion string `yaml:"apiVersion"`
	Kind       string
	Metadata   Metadata
	Spec       ServiceSpec
}
