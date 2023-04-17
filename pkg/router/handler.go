package router

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"
	"runtime"
	"starting-point/pkg/enum"

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
		config.Logger.Info("Request: %s %s", r.Method, r.URL.String())

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
	if int16(runtime.NumGoroutine()) > config.Limiter.GoroutineLimit {
		w.WriteHeader(http.StatusTooManyRequests)

		return
	}

	h.ServeHTTP(w, r)
}

// HandleStatus checks if HTTP is working correctly
func HandleStatus(w http.ResponseWriter, _ *http.Request) {
	writeResponse(w, map[string]interface{}{
		"database": storage.Mongo.IsConnected(),
		"metrics":  service.RabbitMq.IsMetricsConnected(),
	})
}

// HandleRunByID runs topology by ID
func HandleRunByID(w http.ResponseWriter, r *http.Request) {
	handleByID(w, r)
}

// HandleRunByName runs topology by name
func HandleRunByName(w http.ResponseWriter, r *http.Request) {
	handleByName(w, r)
}

// HandleRunByApplication runs topology by application
func HandleRunByApplication(w http.ResponseWriter, r *http.Request) {
	handleByApplication(w, r)
}

// HandleInvalidateCache invalidates topology cache
func HandleInvalidateCache(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	cache := service.Cache.InvalidateCache(vars["topology"])

	writeResponse(w, map[string]interface{}{"cache": cache})
}

func handleByID(w http.ResponseWriter, r *http.Request) {
	err := utils.ValidateBody(r)

	uiRun := false
	allowedTypes := []string{enum.NodeType_Cron, enum.NodeType_Start}
	if r != nil && r.URL != nil && r.URL.Query().Has("uiRun") {
		uiRun = r.URL.Query().Get("uiRun") == "true"
		allowedTypes = append(allowedTypes, enum.NodeType_Webhook)
	}

	if err != nil {
		config.Logger.Error(fmt.Errorf("content is not valid: %s, Route: %s", err.Error(), r.URL.String()))
		writeErrorResponse(w, http.StatusBadRequest, "Content is not valid!")
		return
	}

	init := utils.InitFields()
	vars := mux.Vars(r)
	topology := service.Cache.FindTopologyByID(vars["topology"], vars["node"], uiRun, allowedTypes)

	if topology == nil {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Topology with key '%s' not found!", vars["topology"]))
		return
	}

	if topology.Node == nil {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Node with key '%s' not found!", vars["node"]))
		return
	}

	processMessage(w, r, topology, init)
}

func handleByName(w http.ResponseWriter, r *http.Request) {
	err := utils.ValidateBody(r)
	if err != nil {
		config.Logger.Error(fmt.Errorf("content is not valid: %s, Route: %s", err.Error(), r.URL.String()))
		writeErrorResponse(w, http.StatusBadRequest, "Content is not valid!")
		return
	}

	init := utils.InitFields()
	vars := mux.Vars(r)

	topology := service.Cache.FindTopologyByName(vars["topology"], vars["node"])

	if topology == nil {
		writeErrorResponse(w, http.StatusNotFound, fmt.Sprintf("Topology with name '%s' and node with name '%s' not found!", vars["topology"], vars["node"]))
		return
	}

	processMessage(w, r, topology, init)
}

func handleByApplication(w http.ResponseWriter, r *http.Request) {
	err := utils.ValidateBody(r)
	if err != nil {
		config.Logger.Error(fmt.Errorf("content is not valid: %s, Route: %s", err.Error(), r.URL.String()))
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

	processMessage(w, r, topology, init)
}

func processMessage(w http.ResponseWriter, r *http.Request, topology *storage.Topology, init map[string]float64) {
	r.Header.Set(utils.UserID, getUser(r))

	corrId, _ := service.RabbitMq.SendMessage(r, *topology, init)

	writeResponse(w, map[string]interface{}{"state": "ok", "started": 1, "correlation_id": corrId})
}

func getUser(r *http.Request) string {
	if user := mux.Vars(r)["user"]; user != "" {
		return user
	}

	if data, err := ioutil.ReadAll(r.Body); err == nil {
		innerData := map[string]interface{}{}

		if r.Body.Close() != nil {
			config.Logger.Error(fmt.Errorf("Close stream error: %s", err))
		}

		r.Body = ioutil.NopCloser(bytes.NewBuffer(data))

		if json.Unmarshal(data, &innerData) == nil {
			if user, exists := innerData[utils.UserID]; exists {
				return fmt.Sprintf("%v", user)
			}
		}
	}

	if user := r.Header.Get(utils.UserID); user != "" {
		return user
	}

	return "orchesty"
}
