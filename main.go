package main

import (
	"detector/pkg/enum"
	"time"

	"detector/pkg/config"
	"detector/pkg/services"

	log "github.com/sirupsen/logrus"
)

func main() {
	rb := services.NewRabbitMqFetchSvc()
	var kb services.KubernetesSvc
	if config.Generator.Mode == string(enum.Adapter_Kubernetes) {
		kb = services.NewKubernetesSvc()
	}

	log.Infof("Starting detector, ticks every [%d] secs", config.App.Tick/time.Second)

	// Publisher
	workQueue := make(chan interface{}, 10)
	svc := services.NewSenderSvc(workQueue)
	go svc.Start()

	// Consumer
	for range time.Tick(config.App.Tick) {
		if queues, err := rb.GatherQueuesInfo(); err == nil {
			workQueue <- queues
		} else {
			log.Errorf("failed to load rabbitmq data: %s", err)
		}

		var containers []services.Container
		var err error
		switch config.Generator.Mode {
		case string(enum.Adapter_Compose):
			containers, err = services.DockerContainerCheck()
		case string(enum.Adapter_Kubernetes):
			containers, err = kb.KubeContainerCheck()
		}
		if containers != nil && err == nil {
			workQueue <- containers
		} else {
			log.Errorf("failed to load container data: %s", err)
		}
	}
}
