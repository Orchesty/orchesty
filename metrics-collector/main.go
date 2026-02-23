package main

import (
	"context"
	"os"
	"os/signal"
	"syscall"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/metrics"
	"metrics-collector/pkg/metrics/kubernetes"
	"metrics-collector/pkg/metrics/loki"
	"metrics-collector/pkg/metrics/mongodb"
	"metrics-collector/pkg/metrics/rabbitmq"
	"metrics-collector/pkg/scheduler"
	"metrics-collector/pkg/storage"
)

func main() {
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	repo, err := storage.NewMongoRepository(ctx)
	if err != nil {
		config.Logger.FatalWrap("Failed to connect to MongoDB", err)
	}
	defer repo.Close()

	config.Logger.Info("Connected to MongoDB", map[string]interface{}{
		"dsn": config.Mongo.Dsn,
	})

	collectors := []metrics.Collector{}

	rmqCollector := rabbitmq.NewCollector()
	collectors = append(collectors, rmqCollector)

	mongoDbCollector := mongodb.NewCollector(repo.GetDB())
	collectors = append(collectors, mongoDbCollector)

	if config.Kubernetes.Enabled {
		k8sCollector, err := kubernetes.NewCollector()
		if err != nil {
			config.Logger.Warn("Failed to init K8s collector", map[string]interface{}{
				"error": err.Error(),
			})
		} else {
			collectors = append(collectors, k8sCollector)
		}
	}

	if config.Loki.Enabled {
		lokiCollector := loki.NewCollector()
		collectors = append(collectors, lokiCollector)
	}

	config.Logger.Info("Initialized collectors", map[string]interface{}{
		"count": len(collectors),
	})

	sch := scheduler.NewScheduler(collectors, repo)
	if err := sch.Start(context.Background()); err != nil {
		config.Logger.FatalWrap("Failed to start scheduler", err)
	}

	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)

	<-sigChan
	config.Logger.Info("Received shutdown signal", map[string]interface{}{})

	if err := sch.Stop(); err != nil {
		config.Logger.Warn("Error stopping scheduler", map[string]interface{}{
			"error": err.Error(),
		})
	}

	config.Logger.Info("Application stopped", map[string]interface{}{})
}
