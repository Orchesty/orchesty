package services

import (
	"detector/pkg/config"
	"fmt"
	log "github.com/sirupsen/logrus"
	"time"
)

type Detector struct {
	consumerChecker *ConsumerChecker
	sender          *Sender
	rb              *RabbitMqStats
	containerSystem ContainerSystem
	workQueue       chan interface{}
}

func (d *Detector) Run() {
	log.Infof("Starting detector, ticks every [%d] secs", config.App.Tick/time.Second)

	// Publisher
	go d.sender.Start()

	for range time.Tick(config.App.Tick) {
		if queues, err := d.rb.GatherQueuesInfo(); err == nil {
			d.workQueue <- queues
			d.consumerChecker.ConsumerCheck(queues)
		} else {
			log.Error(fmt.Errorf("Service rabbitmq does not working!"))
			log.Errorf("failed to load rabbitmq data: %s", err)
		}

		if containers, err := d.containerSystem.Check(); containers != nil && err == nil {
			d.workQueue <- containers
		} else {
			log.Errorf("failed to load container data: %s", err)
		}
	}
}

func NewDetector(consumerChecker *ConsumerChecker, workQueue chan interface{}, sender *Sender, rb *RabbitMqStats, containerSystem ContainerSystem) Detector {
	return Detector{consumerChecker: consumerChecker, workQueue: workQueue, sender: sender, rb: rb, containerSystem: containerSystem}
}
