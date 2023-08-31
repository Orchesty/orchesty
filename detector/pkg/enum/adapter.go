package enum

type Adapter string

const (
	Adapter_Compose    Adapter = "compose"
	Adapter_Swarm      Adapter = "swarm"
	Adapter_Kubernetes Adapter = "k8s"
)
