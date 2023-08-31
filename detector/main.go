// Package main comment
package main

import (
	"context"
	"detector/pkg/config"
	"detector/pkg/handler"
	"detector/pkg/services"
	log "github.com/sirupsen/logrus"
	"net/http"
	"os"
	"os/signal"
	"syscall"
)

func main() {
	if err := services.Load(); err != nil {
		log.Error(err)

		panic(err)
	}

	// Consumer
	go services.DIContainer.Detector.Run()

	// Monitoring
	go services.DIContainer.Monitoring.Run()

	server := &http.Server{Addr: ":8080", Handler: handler.Router(handler.Routes())}

	defer func() {
		if err := server.Shutdown(context.Background()); err != nil {
			log.Error(err)
		}
	}()

	gracefulShutdown(server)

	if err := server.ListenAndServe(); err != nil {
		log.Error(err)
	}
}

func gracefulShutdown(server *http.Server) {
	signals := make(chan os.Signal, 1)

	signal.Notify(signals, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		_ = <-signals

		log.Info("Stopping HTTP server...")

		if err := server.Shutdown(context.Background()); err != nil {
			config.Logger.Error(err)
		}

		os.Exit(0)
	}()
}
