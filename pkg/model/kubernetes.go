package model

// Metadata Metadata
type Metadata struct {
	Name string
}

// Selector Selector
type Selector struct {
	MatchLabels map[string]string `yaml:"matchLabels"`
}

// TemplateMetadata TemplateMetadata
type TemplateMetadata struct {
	Labels map[string]string
}

// Port Port
type Port struct {
	Name          string
	ContainerPort int `yaml:"containerPort"`
}

// VolumeMount VolumeMount
type VolumeMount struct {
	Name      string
	MountPath string `yaml:"mountPath"`
}

// EnvItem EnvItem
type EnvItem struct {
	Name  string
	Value string
}

// ResourceLimits ResourceLimits
type ResourceLimits struct {
	Memory string `yaml:"memory"`
	CPU    int    `yaml:"cpu"`
}

// Resources Resources
type Resources struct {
	Limits ResourceLimits `yaml:"limits"`
}

// Container Container
type Container struct {
	Name         string
	Image        string
	Resources    Resources `yaml:"resources"`
	Command      []string
	Args         []string
	Ports        []Port
	Env          []EnvItem
	VolumeMounts []VolumeMount `yaml:"volumeMounts"`
}

// ConfigMapVolume ConfigMapVolume
type ConfigMapVolume struct {
	Name string
}

// Volume Volume
type Volume struct {
	Name      string
	ConfigMap ConfigMapVolume `yaml:"configMap"`
}

// TemplateSpec TemplateSpec
type TemplateSpec struct {
	Containers         []Container
	Volumes            []Volume
	ServiceAccountName string `yaml:"serviceAccountName"`
}

// Template Template
type Template struct {
	Metadata TemplateMetadata
	Spec     TemplateSpec
}

// Spec Spec
type Spec struct {
	Replicas int
	Selector Selector
	Template Template
}

// Deployment Deployment
type Deployment struct {
	APIVersion string `yaml:"apiVersion"`
	Kind       string
	Metadata   Metadata
	Spec       Spec
}

// ConfigMap ConfigMap
type ConfigMap struct {
	APIVersion string `yaml:"apiVersion"`
	Kind       string
	Metadata   Metadata
	Data       map[string]string
}

// ServiceSpec ServiceSpec
type ServiceSpec struct {
	Selector map[string]string
	Ports    []ServicePort
}

// ServicePort ServicePort
type ServicePort struct {
	Protocol   string
	Port       int
	TargetPort string `yaml:"targetPort"`
	Name       string
}

// DeploymentService DeploymentService
type DeploymentService struct {
	APIVersion string `yaml:"apiVersion"`
	Kind       string
	Metadata   Metadata
	Spec       ServiceSpec
}
