package router

import (
	"encoding/json"
	"fmt"
	"github.com/gorilla/mux"
	"net/http"
	"starting-point/pkg/service"

	log "github.com/sirupsen/logrus"
)

// HandleRunByID runs topology by ID
func HandleRunByID(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	var data map[string]interface{}

	err := json.NewDecoder(r.Body).Decode(&data)
	if err != nil {
		log.Error(err)
		writeErrorResponse(w, http.StatusBadRequest, "Content is not a valid JSON!")
		return
	}

	topology := service.FindTopologyByID(vars["topology"], vars["node"])
	if topology == nil {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Topology with key '%s' not found!", vars["topology"]))
		return
	}

	if topology.Node == nil {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Node with key '%s' not found!", vars["node"]))
		return
	}

	writeResponse(w, map[string]interface{}{"topology": topology})
}

// HandleRunByName runs topology by name
func HandleRunByName(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	var data map[string]interface{}

	err := json.NewDecoder(r.Body).Decode(&data)
	if err != nil {
		log.Error(err)
		writeErrorResponse(w, http.StatusBadRequest, "Content is not a valid JSON!")
		return
	}

	topologies := service.FindTopologyByName(vars["topology"], vars["node"])
	if len(topologies) == 0 {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Topology with name '%s' and node with name '%s' not found!", vars["topology"], vars["node"]))
		return
	}

	writeResponse(w, map[string]interface{}{"topologies": topologies})
}

// HandleInvalidateCache invalidates topology cache
func HandleInvalidateCache(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)

	cache := service.InvalidateCache(vars["topology"])

	writeResponse(w, map[string]interface{}{"cache": cache})
}
