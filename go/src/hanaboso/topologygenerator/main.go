package main

// Please set environment DOCKER_API_VERSION, if empty use latest.
// Minimum support version is 1.30

import (
	"fmt"
	"os"
	"net/http"

	"github.com/docker/docker/api/types"
	"github.com/spf13/viper"

	"hanaboso/topologygenerator/log"
	"hanaboso/topologygenerator/handlers"
	"hanaboso/topologygenerator/router"
	"hanaboso/topologygenerator/docker"
	"hanaboso/topologygenerator/commands"
)

var compiled = "NA"

func main() {
	log.Infof("App compiled version: %s", compiled)

	checkEnvironment()

	handler := handlers.CreateHandler(viper.GetString("generator.mode"))
	if handler == nil {
		log.Fatalf("Unknown handler type: %s", viper.GetString("generator.mode"))
	}

	defer handler.Close()

	var routes = router.Routes{
		router.Route{
			Name:        "TopologyGenerate",
			Method:      "GET",
			Pattern:     "/api/topology/generate/{topologyId}",
			HandlerFunc: handler.GenerateAction,
		},
		router.Route{
			Name:        "TopologyRun",
			Method:      "GET",
			Pattern:     "/api/topology/run/{topologyId}",
			HandlerFunc: handler.RunAction,
		},
		router.Route{
			Name:        "TopologyStop",
			Method:      "GET",
			Pattern:     "/api/topology/stop/{topologyId}",
			HandlerFunc: handler.StopAction,
		},
		router.Route{
			Name:        "TopologyDelete",
			Method:      "GET",
			Pattern:     "/api/topology/delete/{topologyId}",
			HandlerFunc: handler.DeleteAction,
		},
		router.Route{
			Name:        "TopologyInfo",
			Method:      "GET",
			Pattern:     "/api/topology/info/{topologyId}",
			HandlerFunc: handler.InfoAction,
		},
	}

	router := router.Router(routes)

	server := fmt.Sprintf("%s:%d", viper.GetString("service.host"), viper.GetInt("service.port"))
	log.Info("Start http server " + server)
	log.Fatal(http.ListenAndServe(server, router))
}

// checkEnvironment runs app environment check and fails the program if some check fails
func checkEnvironment() {
	defer checkExit()

	// check docker.socket ability to handle commands
	docker.ContainerList(types.ContainerListOptions{})
	log.Info("Docker daemon socket check - OK")

	commands.WriteFile(
		fmt.Sprintf("%s/%s", viper.GetString("generator.path"), "check"),
		"check.txt",
		[]byte("check"),
	)
	log.Info("Topology folder writeable - OK")
}

func checkExit() {
	if r := recover(); r!= nil {
		log.Info(fmt.Sprintf("Check failed. %s --> %s", r, "Exiting program."))

		os.Exit(1)
	}
}
