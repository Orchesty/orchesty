package cmd

import (
	"context"
	"fmt"
	"github.com/docker/docker/client"
	"github.com/spf13/cobra"
	"k8s.io/client-go/kubernetes"
	"net/http"
	"os"
	"os/signal"
	"time"
	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
	"topology-generator/pkg/server"
	"topology-generator/pkg/services"
	"topology-generator/pkg/storage"

	log "github.com/hanaboso/go-log/pkg"
)

func startServer(sc *services.ServiceContainer) *http.Server {
	s := server.New(sc)

	go func() {
		logContext(config.Logger, map[string]interface{}{
			"address": s.Addr,
		}).Info("Starting API server...")
		// service connections
		if err := s.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			logContext(config.Logger, map[string]interface{}{
				"address": s.Addr,
			}).Fatal(err) //todo: fatal nikdy neni dobre zvlaste v gorutine
		}
	}()

	return s
}

func serverCommand(_ *cobra.Command, _ []string) error {
	var docker *client.Client
	var clientSet *kubernetes.Clientset
	var err error

	switch model.Adapter(config.Generator.Mode) {
	case model.ModeKubernetes:
		cfg, err := services.GetKubernetesConfig(config.Generator)
		if err != nil {
			return fmt.Errorf("APi server shutdown, reason: %v", err)
		}
		clientSet, err = kubernetes.NewForConfig(cfg)
		if err != nil {
			return fmt.Errorf("APi server shutdown, reason: %v", err)
		}
	case model.ModeCompose, model.ModeSwarm:
		docker, err = services.DockerConnect()
		if err != nil {
			return fmt.Errorf("APi server shutdown, reason: %v", err)
		}
	default:
		return fmt.Errorf("Uknown generator mode %s", config.Generator.Mode)
	}

	mongo := storage.CreateMongo()
	sc := services.NewServiceContainer(mongo, docker, clientSet, config.Generator)
	s := startServer(sc)

	// Wait for interrupt signal to gracefully shutdown the server with
	// a timeout of 5 seconds.
	quit := make(chan os.Signal)
	signal.Notify(quit, os.Interrupt)
	<-quit

	logContext(config.Logger, nil).Info("Shutdown API server...")

	shutdownCtx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	if err := s.Shutdown(shutdownCtx); err != nil {
		return fmt.Errorf("API server shutdown failed, reason: %v", err)
	}
	logContext(config.Logger, nil).Info("Server exiting")
	return nil
}

func logContext(logger log.Logger, data map[string]interface{}) log.Logger {
	if data == nil {
		data = make(map[string]interface{})
	}
	data["service"] = "topology-generator"
	data["type"] = "server"

	return logger.WithFields(data)
}

func init() {
	rootCmd.AddCommand(&cobra.Command{
		Use:   "server",
		Short: "start API server",
		Long:  ``,
		RunE:  serverCommand,
	})
}
