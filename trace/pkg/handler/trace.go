package handler

import (
	"net/http"

	"trace/pkg/service"
	"trace/pkg/utils"
)

// HandleTrace upgrades the HTTP request to a WebSocket. Authentication is intentionally not
// validated here because browser WebSocket clients cannot send custom headers — the client must
// send a {type: "token"} message as the first frame after the connection opens, which is then
// validated against the backend (see traceService.handleToken).
func HandleTrace(writer http.ResponseWriter, request *http.Request) {
	userID := request.URL.Query().Get("user")
	if userID == "" {
		writeErrorResponse(writer, &utils.Error{Code: http.StatusBadRequest, Message: "Missing 'user' query parameter"})

		return
	}

	service.Container.TraceService.HandleConnection(writer, request, userID)
}
