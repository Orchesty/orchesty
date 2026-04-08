package handler

import (
	"net/http"

	"trace/pkg/service"
)

func HandleStatus(writer http.ResponseWriter, _ *http.Request) {
	writeResponse(writer, service.Container.StatusService.Status())
}
