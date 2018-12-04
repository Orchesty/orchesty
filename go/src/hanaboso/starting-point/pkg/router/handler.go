package router

import (
	"fmt"
	"github.com/gorilla/context"
	"github.com/gorilla/mux"
	"net/http"
	"starting-point/pkg/service"
	"starting-point/pkg/utils"

	log "github.com/sirupsen/logrus"
)

// HandleClear handles context clear
func HandleClear(h http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		defer context.Clear(r)

		h.ServeHTTP(w, r)
	}
}

// HandleRunByID runs topology by ID
func HandleRunByID(w http.ResponseWriter, r *http.Request) {
	err := utils.ValidateBody(r)
	if err != nil {
		log.Error(err)
		writeErrorResponse(w, http.StatusBadRequest, "Content is not valid!")
		return
	}

	vars := mux.Vars(r)
	topology := service.Cache.FindTopologyByID(vars["topology"], vars["node"])
	if topology == nil {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Topology with key '%s' not found!", vars["topology"]))
		return
	}

	if topology.Node == nil {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Node with key '%s' not found!", vars["node"]))
		return
	}

	go service.RabbitMq.SndMessage(r, *topology)

	writeResponse(w, map[string]interface{}{"state": "ok", "started": 1})
}

// HandleRunByName runs topology by name
func HandleRunByName(w http.ResponseWriter, r *http.Request) {
	err := utils.ValidateBody(r)
	if err != nil {
		log.Error(err)
		writeErrorResponse(w, http.StatusBadRequest, "Content is not valid!")
		return
	}

	vars := mux.Vars(r)
	topologies := service.Cache.FindTopologyByName(vars["topology"], vars["node"])
	if len(topologies) == 0 {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Topology with name '%s' and node with name '%s' not found!", vars["topology"], vars["node"]))
		return
	}

	for _, topology := range topologies {
		go service.RabbitMq.SndMessage(r, topology)
	}

	writeResponse(w, map[string]interface{}{"state": "ok", "started": len(topologies)})
}

// HandleInvalidateCache invalidates topology cache
func HandleInvalidateCache(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	cache := service.Cache.InvalidateCache(vars["topology"])

	writeResponse(w, map[string]interface{}{"cache": cache})
}
