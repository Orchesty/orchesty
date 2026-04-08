package handler

import (
	"net/http"

	"notifier/pkg/service"
)

func HandleStatus(writer http.ResponseWriter, _ *http.Request) {
	writeResponse(writer, service.Container.StatusService.Status())
}
