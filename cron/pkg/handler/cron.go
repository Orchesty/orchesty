package handler

import (
	"encoding/json"
	"net/http"

	"github.com/julienschmidt/httprouter"

	"cron/pkg/config"
	"cron/pkg/model"
	"cron/pkg/service"

	log "github.com/hanaboso/go-log/pkg"
)

func HandleSelect(writer http.ResponseWriter, _ *http.Request, _ httprouter.Params) {
	crons, err := service.Container.CronService.Select()

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

func HandleUpsert(writer http.ResponseWriter, request *http.Request, _ httprouter.Params) {
	var crons []model.Cron

	if err := json.NewDecoder(request.Body).Decode(&crons); err != nil {
		logContext().Error(err)
		writeErrorResponse(writer, err)

		return
	}

	logContext().Info("Upsert CRONs: %s", logCrons(crons))

	if err := service.Container.CronService.Upsert(crons); err != nil {
		writeErrorResponse(writer, err)

		return
	}

	writeSuccessResponse(writer)
}

func HandleDelete(writer http.ResponseWriter, request *http.Request, _ httprouter.Params) {
	var crons []model.Cron

	if err := json.NewDecoder(request.Body).Decode(&crons); err != nil {
		logContext().Error(err)
		writeErrorResponse(writer, err)

		return
	}

	logContext().Info("Delete CRONs: %s", logCrons(crons))

	if err := service.Container.CronService.Delete(crons); err != nil {
		writeErrorResponse(writer, err)

		return
	}

	writeSuccessResponse(writer)
}

func logCrons(crons []model.Cron) string {
	data, err := json.Marshal(crons)

	if err != nil {
		logContext().Error(err)

		return ""
	}

	return string(data)
}

func logContext() log.Logger {
	return config.Logger.WithFields(map[string]interface{}{
		"service": "CRON",
		"type":    "Handler",
	})
}
