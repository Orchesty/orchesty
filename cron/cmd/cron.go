package main

import (
	"context"
	"net/http"
	"os"
	"os/signal"
	"syscall"

	"cron/pkg/config"
	"cron/pkg/router"
	"cron/pkg/service"
	"cron/pkg/storage"

	log "github.com/sirupsen/logrus"
)

func main() {
	config.Config.Logger.Info("Starting HTTP server...")
	storage.MongoDB.Connect()
	service.Cron.Start()

	server := &http.Server{Addr: ":8080", Handler: router.Router(router.Routes())}

	defer func() {
		storage.MongoDB.Disconnect()
		service.Cron.Stop()

		if err := server.Shutdown(context.Background()); err != nil {
			config.Config.Logger.Error(err)
		}
	}()

	gracefulShutdown(server)

	if err := server.ListenAndServe(); err != nil {
		config.Config.Logger.Error(err)
	}
}

func gracefulShutdown(server *http.Server) {
	signals := make(chan os.Signal, 1)

	signal.Notify(signals, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		_ = <-signals

		log.Info("Stopping HTTP server...")
		storage.MongoDB.Disconnect()
		service.Cron.Stop()

		if err := server.Shutdown(context.Background()); err != nil {
			config.Config.Logger.Error(err)
		}

		os.Exit(0)
	}()
}
