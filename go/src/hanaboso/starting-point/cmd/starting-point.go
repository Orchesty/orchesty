package main

import (
	"context"
	"fmt"
	"net/http"
	"os"
	"os/signal"
	"starting-point/pkg/router"
	"starting-point/pkg/service"
	"starting-point/pkg/storage"
	"starting-point/pkg/udp"
	"syscall"

	log "github.com/sirupsen/logrus"
)

func main() {
	var routes = router.Routes{
		router.Route{
			Name:        "Status",
			Method:      "GET",
			Pattern:     "/starting-point/status",
			HandlerFunc: router.HandleClear(router.HandleStatus),
		},
		router.Route{
			Name:        "Run topology by ID",
			Method:      "POST",
			Pattern:     "/starting-point/topologies/{topology}/nodes/{node}/run",
			HandlerFunc: router.HandleClear(router.HandleRunByID),
		},
		router.Route{
			Name:        "Run topology by name",
			Method:      "POST",
			Pattern:     "/starting-point/topologies/{topology}/nodes/{node}/run-by-name",
			HandlerFunc: router.HandleClear(router.HandleRunByName),
		},
		router.Route{
			Name:        "Run human task topology by ID",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/run",
			HandlerFunc: router.HandleClear(router.HandleHumanTaskRunByID),
		},
		router.Route{
			Name:        "Run human task topology by ID with token",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/token/{token}/run",
			HandlerFunc: router.HandleClear(router.HandleHumanTaskRunByID),
		},
		router.Route{
			Name:        "Run human task topology by name",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/run-by-name",
			HandlerFunc: router.HandleClear(router.HandleHumanTaskRunByName),
		},
		router.Route{
			Name:        "Run human task topology by name with token",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/token/{token}/run-by-name",
			HandlerFunc: router.HandleClear(router.HandleHumanTaskRunByName),
		},
		router.Route{
			Name:        "Stop human task topology by ID",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/stop",
			HandlerFunc: router.HandleClear(router.HandleHumanTaskStopByID),
		},
		router.Route{
			Name:        "Stop human task topology by ID with token",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/token/{token}/stop",
			HandlerFunc: router.HandleClear(router.HandleHumanTaskStopByID),
		},
		router.Route{
			Name:        "Stop human task topology by name",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/stop-by-name",
			HandlerFunc: router.HandleClear(router.HandleHumanTaskStopByName),
		},
		router.Route{
			Name:        "Stop human task topology by name with token",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/token/{token}/stop-by-name",
			HandlerFunc: router.HandleClear(router.HandleHumanTaskStopByName),
		},
		router.Route{
			Name:        "Invalidate topology cache",
			Method:      "POST",
			Pattern:     "/starting-point/topologies/{topology}/invalidate-cache",
			HandlerFunc: router.HandleClear(router.HandleInvalidateCache),
		},
	}

	log.Info("Starting server...")
	storage.CreateMongo()
	service.CreateCache()
	service.ConnectToRabbit()
	udp.ConnectToUDP()
	server := &http.Server{Addr: fmt.Sprint(":80"), Handler: router.Router(routes)}

	defer func() {
		service.RabbitMq.DisconnectRabbit()
		udp.UDPSender.DisconnectUDP()
		_ = storage.Mongo.Disconnect()
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
		service.RabbitMq.DisconnectRabbit()
		udp.UDPSender.DisconnectUDP()
		_ = storage.Mongo.Disconnect()
		_ = server.Shutdown(context.Background())

		os.Exit(0)
	}()
}
