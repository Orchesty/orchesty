package router

import (
	"encoding/json"
	"net/http"

	"cron/pkg/config"
	"cron/pkg/storage"
	"github.com/julienschmidt/httprouter"
)

const topology = "topology"
const node = "node"

// HandleStatus checks application status
func HandleStatus(writer http.ResponseWriter, _ *http.Request, _ httprouter.Params) {
	writeResponse(writer, map[string]interface{}{
		"database": storage.MongoDB.IsConnected(),
	})
}

// HandleGetAll returns all crons
func HandleGetAll(writer http.ResponseWriter, _ *http.Request, _ httprouter.Params) {
	crons, err := storage.MongoDB.GetAll()

	if err != nil {
		writeErrorResponse(writer, err)

		return
	}

	if len(crons) == 0 {
		writeResponse(writer, []map[string]interface{}{})

		return
	}

	writeResponse(writer, crons)
}

// HandleCreate creates cron
func HandleCreate(writer http.ResponseWriter, request *http.Request, _ httprouter.Params) {
	var cron storage.Cron

	if err := json.NewDecoder(request.Body).Decode(&cron); err != nil {
		logJSONError(err)
		writeErrorResponse(writer, err)

		return
	}

	if _, err := storage.MongoDB.Create(&cron); err != nil {
		writeErrorResponse(writer, err)

		return
	}

	writeSuccessResponse(writer)
}

// HandleUpdate updates cron
func HandleUpdate(writer http.ResponseWriter, request *http.Request, parameters httprouter.Params) {
	var cron storage.Cron

	if err := json.NewDecoder(request.Body).Decode(&cron); err != nil {
		logJSONError(err)
		writeErrorResponse(writer, err)

		return
	}

	cron.Topology = parameters.ByName(topology)
	cron.Node = parameters.ByName(node)

	if _, err := storage.MongoDB.Update(&cron); err != nil {
		writeErrorResponse(writer, err)

		return
	}

	writeSuccessResponse(writer)
}

// HandleUpsert upserts cron
func HandleUpsert(writer http.ResponseWriter, request *http.Request, parameters httprouter.Params) {
	var cron storage.Cron

	if err := json.NewDecoder(request.Body).Decode(&cron); err != nil {
		logJSONError(err)
		writeErrorResponse(writer, err)

		return
	}

	cron.Topology = parameters.ByName(topology)
	cron.Node = parameters.ByName(node)

	if _, err := storage.MongoDB.Upsert(&cron); err != nil {
		writeErrorResponse(writer, err)

		return
	}

	writeSuccessResponse(writer)
}

// HandleDelete deletes cron
func HandleDelete(writer http.ResponseWriter, _ *http.Request, parameters httprouter.Params) {
	if _, err := storage.MongoDB.Delete(&storage.Cron{
		Topology: parameters.ByName(topology),
		Node:     parameters.ByName(node),
	}); err != nil {
		writeErrorResponse(writer, err)

		return
	}

	writeSuccessResponse(writer)
}

// HandleBatchCreate creates crons
func HandleBatchCreate(writer http.ResponseWriter, request *http.Request, _ httprouter.Params) {
	var crons []storage.Cron

	if err := json.NewDecoder(request.Body).Decode(&crons); err != nil {
		logJSONError(err)
		writeErrorResponse(writer, err)

		return
	}

	if _, err := storage.MongoDB.BatchCreate(crons); err != nil {
		writeErrorResponse(writer, err)

		return
	}

	writeSuccessResponse(writer)
}

// HandleBatchUpdate updates crons
func HandleBatchUpdate(writer http.ResponseWriter, request *http.Request, _ httprouter.Params) {
	var crons []storage.Cron

	if err := json.NewDecoder(request.Body).Decode(&crons); err != nil {
		logJSONError(err)
		writeErrorResponse(writer, err)

		return
	}

	if _, err := storage.MongoDB.BatchUpdate(crons); err != nil {
		writeErrorResponse(writer, err)

		return
	}

	writeSuccessResponse(writer)
}

// HandleBatchUpsert upserts crons
func HandleBatchUpsert(writer http.ResponseWriter, request *http.Request, _ httprouter.Params) {
	var crons []storage.Cron

	if err := json.NewDecoder(request.Body).Decode(&crons); err != nil {
		logJSONError(err)
		writeErrorResponse(writer, err)

		return
	}

	if _, err := storage.MongoDB.BatchUpsert(crons); err != nil {
		writeErrorResponse(writer, err)

		return
	}

	writeSuccessResponse(writer)
}

// HandleBatchDelete deletes crons
func HandleBatchDelete(writer http.ResponseWriter, request *http.Request, _ httprouter.Params) {
	var crons []storage.Cron

	if err := json.NewDecoder(request.Body).Decode(&crons); err != nil {
		logJSONError(err)
		writeErrorResponse(writer, err)

		return
	}

	if _, err := storage.MongoDB.BatchDelete(crons); err != nil {
		writeErrorResponse(writer, err)

		return
	}

	writeSuccessResponse(writer)
}

func logJSONError(error error) {
	config.Config.Logger.Errorf("Unexpected JSON error: %s", error.Error())
}
