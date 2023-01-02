package handler

import (
	"detector/pkg/services"
	"github.com/julienschmidt/httprouter"
	"net/http"
)

func HandleMetrics(writer http.ResponseWriter, _ *http.Request, _ httprouter.Params) {
	writeResponse(writer, services.DIContainer.Monitoring.FormatResult())
}
