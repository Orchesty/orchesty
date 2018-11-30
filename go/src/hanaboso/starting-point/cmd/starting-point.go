package main

import (
	"context"
	"fmt"
	"net/http"
	"os"
	"os/signal"
	"starting-point/pkg/router"
	"starting-point/pkg/storage"
	"syscall"

	log "github.com/sirupsen/logrus"
)

func main() {

	var routes = router.Routes{
		router.Route{
			Name:        "Status",
			Method:      "GET",
			Pattern:     "/status",
			HandlerFunc: router.HandleStatus,
		},
		router.Route{
			Name:        "Run by ID",
			Method:      "POST",
			Pattern:     "/starting-point/topologies/{topology}/nodes/{node}/run",
			HandlerFunc: router.HandleRunByID,
		},
		router.Route{
			Name:        "Run by name",
			Method:      "POST",
			Pattern:     "/starting-point/topologies/{topology}/nodes/{node}/run-by-name",
			HandlerFunc: router.HandleRunByName,
		},
	}

	log.Info("Starting server...")
	storage.CreateConnection()
	server := &http.Server{Addr: fmt.Sprint(":80"), Handler: router.Router(routes)}

	defer func() {
		_ = storage.MongoDB.Client().Disconnect(context.Background())
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
		_ = storage.MongoDB.Client().Disconnect(context.Background())
		_ = server.Shutdown(context.Background())

		os.Exit(0)
	}()
}
