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

	log "github.com/hanaboso/go-log/pkg"
)

func main() {
	logContext().Info("Starting HTTP server...")
	storage.MongoDB.Connect()
	service.Cron.Start()

	server := &http.Server{Addr: ":8080", Handler: router.Router(router.Routes())}

	defer func() {
		storage.MongoDB.Disconnect()
		service.Cron.Stop()

		if err := server.Shutdown(context.Background()); err != nil {
			logContext().Error(err)
		}
	}()

	gracefulShutdown(server)

	if err := server.ListenAndServe(); err != nil {
		logContext().Error(err)
	}
}

func gracefulShutdown(server *http.Server) {
	signals := make(chan os.Signal, 1)

	signal.Notify(signals, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		_ = <-signals

		logContext().Info("Stopping HTTP server...")
		storage.MongoDB.Disconnect()
		service.Cron.Stop()

		if err := server.Shutdown(context.Background()); err != nil {
			logContext().Error(err)
		}

		os.Exit(0)
	}()
}

func logContext() log.Logger {
	return config.Logger.WithFields(map[string]interface{}{
		"service": "cron",
		"type":    "cmd",
	})
}
