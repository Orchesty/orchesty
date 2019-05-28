package cmd

import (
	"context"
	"net/http"
	"os"
	"os/signal"
	"time"

	"github.com/gin-gonic/gin"
	log "github.com/sirupsen/logrus"
	"github.com/spf13/cobra"

	"topology-generator/pkg/docker_client"
	"topology-generator/pkg/server"
	"topology-generator/pkg/storage"
)

func startServer(mongo *storage.MongoDefault, docker *docker_client.DockerApiClient) *http.Server {
	s := server.New(mongo, docker)

	go func() {
		log.WithField("address", s.Addr).Info("Starting API server...")
		// service connections
		if err := s.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.WithField("address", s.Addr).Fatal("API server start failed, reason:", err)
		}
	}()

	return s
}

func serverCommand(cmd *cobra.Command, args []string) {
	mongo := storage.CreateMongo()

	docker, err := docker_client.CreateClient()
	if err != nil {

	}
	s := startServer(mongo, docker)

	// Wait for interrupt signal to gracefully shutdown the server with
	// a timeout of 5 seconds.
	quit := make(chan os.Signal)
	signal.Notify(quit, os.Interrupt)
	<-quit

	log.Info("Shutdown API server...")

	shutdownCtx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	if err := s.Shutdown(shutdownCtx); err != nil {
		log.Fatal("API server shutdown failed, reason:", err)
	}
	log.Info("Server exiting")
}

func init() {
	if gin.IsDebugging() {
		log.SetLevel(log.DebugLevel)
	}

	rootCmd.AddCommand(&cobra.Command{
		Use:   "server",
		Short: "Start API server",
		Long:  ``,
		Run:   serverCommand,
	})
}
