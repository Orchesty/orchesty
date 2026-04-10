package main

import (
	"context"
	"errors"
	"fmt"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/ingressGW"
	"cloud-controller/pkg/kubernetes"
	"cloud-controller/pkg/mongodb"
	"cloud-controller/pkg/objectStorage"
	"cloud-controller/pkg/rabbitmq"
	"cloud-controller/pkg/server"
	"cloud-controller/pkg/service"
)

const shutdownTimeout = 10 * time.Second

func main() {
	mongoClient := mongodb.NewClient()
	if err := mongoClient.Init(); err != nil {
		config.Logger.Fatal(err)
	}

	rabbitClient := rabbitmq.NewClient()
	kubernetesClient := kubernetes.NewClient()
	kongClient := ingressGW.NewClient()
	gcsClient := objectStorage.NewClient()
	instanceService := service.NewInstanceService(mongoClient, rabbitClient, kubernetesClient, kongClient, gcsClient)
	defer instanceService.Shutdown()

	httpServer := &http.Server{
		Addr:              fmt.Sprintf(":%d", config.App.Port),
		Handler:           server.New(instanceService, mongoClient, rabbitClient, kubernetesClient, kongClient, gcsClient),
		ReadHeaderTimeout: 5 * time.Second,
	}

	go func() {
		config.Logger.Info(fmt.Sprintf("HTTP server listening on :%d", config.App.Port), map[string]interface{}{})
		if err := httpServer.ListenAndServe(); err != nil && !errors.Is(err, http.ErrServerClosed) {
			config.Logger.Fatal(err)
		}
	}()

	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)

	<-sigChan
	config.Logger.Info("Received shutdown signal", map[string]interface{}{})

	ctx, cancel := context.WithTimeout(context.Background(), shutdownTimeout)
	defer cancel()

	if err := httpServer.Shutdown(ctx); err != nil {
		config.Logger.Error(fmt.Errorf("failed to shutdown HTTP server gracefully: %w", err))
		os.Exit(1)
	}

	config.Logger.Info("Application stopped", map[string]interface{}{})
}
