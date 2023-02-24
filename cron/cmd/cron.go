package main

import (
	"context"
	"net/http"
	"os"
	"os/signal"
	"syscall"

	"cron/pkg/config"
	"cron/pkg/handler"
	"cron/pkg/service"

	log "github.com/hanaboso/go-log/pkg"
)

func main() {
	logContext().Info("Connecting to StartingPoint: %s", config.StartingPoint.Dsn)

	if err := service.Load(); err != nil {
		logContext().Error(err)

		panic(err)
	}

	logContext().Info("Starting HTTP server: http://0.0.0.0:8080")
	server := &http.Server{Addr: ":8080", Handler: handler.Router(handler.Routes())}

	defer func() {
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

		if err := server.Shutdown(context.Background()); err != nil {
			logContext().Error(err)
		}

		os.Exit(0)
	}()
}

func logContext() log.Logger {
	return config.Logger.WithFields(map[string]interface{}{
		"service": "CRON",
		"type":    "App",
	})
}
