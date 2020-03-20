package main

import (
	"time"

	"rabbitmq-telegraf/pkg/config"
	"rabbitmq-telegraf/pkg/services"

	log "github.com/sirupsen/logrus"
)

func main() {
	rb := services.NewRabbitMqFetchSvc()
	log.Infof("Starting rabbitmq telegraf, ticks every [%d] secs", config.App.Tick/time.Second)

	// Publisher
	workQueue := make(chan []services.Queue, 10)
	svc := services.NewSenderSvc(workQueue, config.Logger)
	go svc.Start()

	// Consumer
	for range time.Tick(config.App.Tick) {
		queues, err := rb.GatherQueuesInfo()
		if err != nil {
			log.Errorf("failed to load rabbitmq data: %s", err)

			continue
		}

		workQueue <- queues
	}
}
