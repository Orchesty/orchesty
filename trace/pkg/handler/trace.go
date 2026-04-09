package handler

import (
	"net/http"

	"trace/pkg/service"
	"trace/pkg/utils"
)

func HandleTrace(writer http.ResponseWriter, request *http.Request) {
	authHeader := request.Header.Get("Authorization")
	if authHeader == "" {
		writeErrorResponse(writer, &utils.Error{Code: http.StatusUnauthorized, Message: "Missing Authorization header"})

		return
	}

	body, statusCode, err := service.Container.AuthService.CheckLogged(authHeader)
	if err != nil {
		logContext().Error(err)
		writeErrorResponse(writer, &utils.Error{Code: http.StatusBadGateway, Message: "Backend unreachable"})

		return
	}

	if statusCode != http.StatusOK {
		writeErrorResponse(writer, &utils.Error{Code: statusCode}, body)

		return
	}

	userID := request.URL.Query().Get("user")
	if userID == "" {
		writeErrorResponse(writer, &utils.Error{Code: http.StatusBadRequest, Message: "Missing 'user' query parameter"})

		return
	}

	service.Container.TraceService.HandleConnection(writer, request, authHeader, userID)
}
