package main

import (
	"context"
	"fmt"
	"net/http"
	"os"
	"os/signal"
	"syscall"

	"starting-point/pkg/router"
	"starting-point/pkg/service"
	"starting-point/pkg/storage"

	log "github.com/sirupsen/logrus"
)

func main() {
	log.Info("Starting server...")

	// Connect to services
	storage.CreateMongo()
	service.CreateCache()
	service.ConnectToRabbit()
	service.StartCleaner()

	// Start http server
	server := &http.Server{Addr: fmt.Sprint(":8080"), Handler: router.Router(nil)}

	log.Info("Successfully started.")

	defer func() {
		service.RabbitMq.Disconnect()
		storage.Mongo.Disconnect()
		_ = server.Shutdown(context.Background())
	}()

	gracefulShutdown(server)
	_ = server.ListenAndServe()
}

// gracefulShutdown handles SIGINT and SIGTERM signal to stop the app gracefully
func gracefulShutdown(server *http.Server) {
	sigs := make(chan os.Signal, 1)

	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		_ = <-sigs

		log.Info("Stopping server...")
		service.RabbitMq.Disconnect()
		storage.Mongo.Disconnect()
		_ = server.Shutdown(context.Background())

		os.Exit(0)
	}()
}
