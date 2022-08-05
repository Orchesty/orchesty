package services

import (
	"github.com/docker/docker/client"
	"k8s.io/client-go/kubernetes"
	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
	"topology-generator/pkg/storage"
)

// ServiceContainer ServiceContainer
type ServiceContainer struct {
	Mongo      StorageSvc
	Docker     DockerSvc
	Kubernetes KubernetesSvc
	DockerCli  DockerCliSvc
}

// NewServiceContainer NewServiceContainer
func NewServiceContainer(mongo storage.MongoInterface, cli *client.Client, clientSet *kubernetes.Clientset, config config.GeneratorConfig) *ServiceContainer {
	storageSvc := NewStorageSvc(mongo)
	if model.Adapter(config.Mode) == model.ModeKubernetes {
		kubernetesSvc := NewKubernetesSvc(clientSet, config.Namespace)
		return &ServiceContainer{
			Mongo:      storageSvc,
			Kubernetes: kubernetesSvc,
		}
	}

	return &ServiceContainer{
		Mongo:     storageSvc,
		Docker:    NewDockerSvc(),
		DockerCli: NewDockerCliSvc(cli),
	}
}
