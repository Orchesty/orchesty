package services

import (
	"detector/pkg/config"
	"detector/pkg/enum"
	"github.com/hanaboso/go-mongodb"
)

var DIContainer container

type container struct {
	Mongo        *mongodb.Connection
	MetricsMongo *mongodb.Connection
	Monitoring   *Monitoring
	Detector     *Detector
}

func Load() error {
	mongo := &mongodb.Connection{}
	mongo.Connect(config.Mongo.Dsn)

	metricsMongo := &mongodb.Connection{}
	metricsMongo.Connect(config.Metrics.Dsn)

	monitoring := NewMonitoring(mongo, config.Mongo.MultiCounter, config.Mongo.Limiter, config.Mongo.Repeater)

	workQueue := make(chan interface{}, 10)
	svc := NewSenderSvc(workQueue, metricsMongo)

	consumerChecker := NewConsumerCheckerSvc(config.Logger, mongo)
	rb := NewRabbitMqFetchSvc()

	var containerSystem ContainerSystem
	if config.Generator.Mode == string(enum.Adapter_Kubernetes) {
		containerSystem = NewKubernetesSvc()
	} else {
		containerSystem = NewComposeSvc()
	}

	detector := NewDetector(&consumerChecker, workQueue, &svc, &rb, containerSystem)

	DIContainer = container{
		Mongo:        mongo,
		MetricsMongo: metricsMongo,
		Monitoring:   &monitoring,
		Detector:     &detector,
	}

	return nil
}
