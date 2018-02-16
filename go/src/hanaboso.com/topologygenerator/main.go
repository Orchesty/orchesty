package main

// Please set environment DOCKER_API_VERSION, if empty use latest.
// Minimum support version is 1.30

import (
	"fmt"
	"hanaboso.com/topologygenerator/router"
	"log"
	"net/http"
	"github.com/spf13/viper"
	"hanaboso.com/topologygenerator/handlers"
)

var compiled = "NA"

func main() {
	log.Printf("App compiled version: %s", compiled)

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
	log.Println("Start http server " + server)
	log.Fatal(http.ListenAndServe(server, router))
}
