package services

import (
	"github.com/docker/docker/client"
	"k8s.io/client-go/kubernetes"
	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
	"topology-generator/pkg/storage"
)

type ServiceContainer struct {
	Mongo      StorageSvc
	Docker     DockerSvc
	Kubernetes KubernetesSvc
	DockerCli  DockerCliSvc
}

func NewServiceContainer(mongo storage.MongoInterface, cli *client.Client, clientSet *kubernetes.Clientset, config config.GeneratorConfig) *ServiceContainer {
	storageSvc := NewStorageSvc(mongo)
	if config.Mode == model.ModeKubernetes {
		kubernetesSvc := NewKubernetesSvc(clientSet, config.Namespace)
		return &ServiceContainer{
			Mongo:      storageSvc,
			Kubernetes: kubernetesSvc,
		}
	} else {
		return &ServiceContainer{
			Mongo:     storageSvc,
			Docker:    NewDockerSvc(),
			DockerCli: NewDockerCliSvc(cli),
		}
	}
}
