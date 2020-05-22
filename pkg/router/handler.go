package router

import (
	"encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"
	"runtime"

	"github.com/gorilla/context"
	"github.com/gorilla/mux"
	"starting-point/pkg/config"
	"starting-point/pkg/service"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"
)

// HandleClear handles context clear
func HandleClear(h http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		defer context.Clear(r)

		w.Header().Set("Access-Control-Allow-Origin", r.Header.Get("Origin"))
		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type")
		w.Header().Set("Access-Control-Allow-Credentials", "true")
		w.Header().Set("Access-Control-Max-Age", "3600")

		if r.Method == http.MethodOptions {
			w.WriteHeader(http.StatusNoContent)
			return
		}

		HandleLimit(h, w, r)
	}
}

// HandleLimit checks if there is not too many requests
func HandleLimit(h http.HandlerFunc, w http.ResponseWriter, r *http.Request) {
	if int16(runtime.NumGoroutine()) > config.Config.Limiter.GoroutineLimit {
		w.WriteHeader(http.StatusTooManyRequests)

		return
	}

	h.ServeHTTP(w, r)
}

// HandleStatus checks if HTTP is working correctly
func HandleStatus(w http.ResponseWriter, r *http.Request) {
	writeResponse(w, map[string]interface{}{
		"database": storage.Mongo.IsConnected(),
		"metrics":  service.RabbitMq.IsMetricsConnected(),
	})
}

// HandleRunByID runs topology by ID
func HandleRunByID(w http.ResponseWriter, r *http.Request) {
	handleByID(w, r, false, false)
}

// HandleRunByName runs topology by name
func HandleRunByName(w http.ResponseWriter, r *http.Request) {
	handleByName(w, r, false, false)
}

// HandleRunByApplication runs topology by application
func HandleRunByApplication(w http.ResponseWriter, r *http.Request) {
	handleByApplication(w, r)
}

// HandleHumanTaskRunByID runs human task topology by ID
func HandleHumanTaskRunByID(w http.ResponseWriter, r *http.Request) {
	handleByID(w, r, true, false)
}

// HandleHumanTaskRunByName runs human task topology by name
func HandleHumanTaskRunByName(w http.ResponseWriter, r *http.Request) {
	handleByName(w, r, true, false)
}

// HandleHumanTaskStopByID stops human task topology by ID
func HandleHumanTaskStopByID(w http.ResponseWriter, r *http.Request) {
	handleByID(w, r, true, true)
}

// HandleHumanTaskStopByName stops human task topology by name
func HandleHumanTaskStopByName(w http.ResponseWriter, r *http.Request) {
	handleByName(w, r, true, true)
}

// HandleInvalidateCache invalidates topology cache
func HandleInvalidateCache(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	cache := service.Cache.InvalidateCache(vars["topology"])

	writeResponse(w, map[string]interface{}{"cache": cache})
}

func handleByID(w http.ResponseWriter, r *http.Request, isHumanTask, isStop bool) {
	err := utils.ValidateBody(r)
	if err != nil {
		config.Config.Logger.Errorf("Content is not valid: %s", err.Error())
		writeErrorResponse(w, http.StatusBadRequest, "Content is not valid!")
		return
	}

	init := utils.InitFields()
	vars := mux.Vars(r)
	var topology *storage.Topology

	if !isHumanTask {
		topology = service.Cache.FindTopologyByID(vars["topology"], vars["node"], "", isHumanTask)
	} else {
		topology = storage.Mongo.FindTopologyByID(vars["topology"], vars["node"], vars["token"], isHumanTask)
	}

	if topology == nil {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Topology with key '%s' not found!", vars["topology"]))
		return
	}

	if topology.Node == nil {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Node with key '%s' not found!", vars["node"]))
		return
	}

	if isHumanTask && topology.Node.HumanTask == nil {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Human task with token '%s' not found!", vars["token"]))
		return
	}

	if user := getUser(r); user != "" {
		r.Header.Set(utils.UserID, user)
	}

	go processMessage(isHumanTask, isStop, topology, r, init)

	writeResponse(w, map[string]interface{}{"state": "ok", "started": 1})
}

func handleByName(w http.ResponseWriter, r *http.Request, isHumanTask, isStop bool) {
	err := utils.ValidateBody(r)
	if err != nil {
		config.Config.Logger.Errorf("Content is not valid: %s", err.Error())
		writeErrorResponse(w, http.StatusBadRequest, "Content is not valid!")
		return
	}

	init := utils.InitFields()
	vars := mux.Vars(r)
	var topology *storage.Topology

	if !isHumanTask {
		topology = service.Cache.FindTopologyByName(vars["topology"], vars["node"], "", isHumanTask)

		if topology == nil {
			writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Topology with name '%s' and node with name '%s' not found!", vars["topology"], vars["node"]))
			return
		}
	} else {
		topology = storage.Mongo.FindTopologyByName(vars["topology"], vars["node"], vars["token"], isHumanTask)

		if topology == nil {
			writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Topology with name '%s', node with name '%s' and human task with token '%s' not found!", vars["topology"], vars["node"], vars["token"]))
			return
		}
	}

	if user := getUser(r); user != "" {
		r.Header.Set(utils.UserID, user)
	}

	go processMessage(isHumanTask, isStop, topology, r, init)

	writeResponse(w, map[string]interface{}{"state": "ok", "started": 1})
}

func handleByApplication(w http.ResponseWriter, r *http.Request) {
	err := utils.ValidateBody(r)
	if err != nil {
		config.Config.Logger.Errorf("Content is not valid: %s", err.Error())
		writeErrorResponse(w, http.StatusBadRequest, "Content is not valid!")
		return
	}

	init := utils.InitFields()
	vars := mux.Vars(r)
	var topology, webhook = service.Cache.FindTopologyByApplication(vars["topology"], vars["node"], vars["token"])

	if topology == nil || webhook == nil {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Topology with name '%s', node with name '%s' and webhook with token '%s' not found!", vars["topology"], vars["node"], vars["token"]))
		return
	}

	r.Header.Set(utils.ApplicationID, webhook.Application)
	r.Header.Set(utils.UserID, webhook.User)

	go processMessage(false, false, topology, r, init)

	writeResponse(w, map[string]interface{}{"state": "ok", "started": 1})
}

func processMessage(isHumanTask bool, isStop bool, topology *storage.Topology, r *http.Request, init map[string]float64) {
	service.RabbitMq.SndMessage(r, *topology, init, isHumanTask, isStop)
}

func getUser(r *http.Request) string {
	if user := mux.Vars(r)["user"]; user != "" {
		return user
	}

	if data, err := ioutil.ReadAll(r.Body); err == nil {
		innerData := map[string]interface{}{}

		if json.Unmarshal(data, &innerData) == nil {
			if user, exists := innerData[utils.UserID]; exists {
				return fmt.Sprintf("%v", user)
			}
		}
	}

	return r.Header.Get(utils.UserID)
}
