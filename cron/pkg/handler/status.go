package handler

import (
	"net/http"

	"github.com/julienschmidt/httprouter"

	"cron/pkg/service"
)

func HandleStatus(writer http.ResponseWriter, _ *http.Request, _ httprouter.Params) {
	writeResponse(writer, service.Container.StatusService.Status())
}
