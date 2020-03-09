package router

import (
	"encoding/json"
	"net/http"
	"reflect"

	"cron/pkg/utils"
	"github.com/julienschmidt/httprouter"
)

const (
	contentType     = "Content-Type"
	applicationJSON = "application/json; charset=utf-8"
)

// Route represents HTTP route configuration
type Route struct {
	Name    string
	Method  string
	Pattern string
	Handler httprouter.Handle
}

// Router creates HTTP router
func Router(routes []Route) *httprouter.Router {
	router := httprouter.New()
	options := map[string]bool{}

	for _, route := range routes {
		router.Handle(route.Method, route.Pattern, corsHandler(route.Handler))

		if !options[route.Pattern] {
			options[route.Pattern] = true
			router.Handle(http.MethodOptions, route.Pattern, corsHandler(nil))
		}
	}

	router.NotFound = http.HandlerFunc(notFoundHandler)
	router.MethodNotAllowed = http.HandlerFunc(methodNotAllowedHandler)

	return router
}

func corsHandler(handle httprouter.Handle) httprouter.Handle {
	return func(writer http.ResponseWriter, request *http.Request, parameters httprouter.Params) {
		if origin := request.Header.Get("Origin"); origin != "" {
			writer.Header().Set("Access-Control-Allow-Origin", origin)
		} else {
			writer.Header().Set("Access-Control-Allow-Origin", "*")
		}

		writer.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
		writer.Header().Set("Access-Control-Allow-Headers", "Content-Type")
		writer.Header().Set("Access-Control-Allow-Credentials", "true")
		writer.Header().Set("Access-Control-Max-Age", "3600")

		if request.Method == http.MethodOptions {
			writer.WriteHeader(http.StatusNoContent)

			return
		}

		handle(writer, request, parameters)
	}
}

func notFoundHandler(writer http.ResponseWriter, _ *http.Request) {
	writer.Header().Set(contentType, applicationJSON)
	writer.WriteHeader(http.StatusNotFound)
}

func methodNotAllowedHandler(writer http.ResponseWriter, _ *http.Request) {
	writer.Header().Set(contentType, applicationJSON)
	writer.WriteHeader(http.StatusMethodNotAllowed)
}

func writeResponse(writer http.ResponseWriter, content interface{}) {
	writer.Header().Set(contentType, applicationJSON)

	if err := json.NewEncoder(writer).Encode(content); err != nil {
		logJSONError(err)
	}
}

func writeSuccessResponse(writer http.ResponseWriter) {
	writer.Header().Set(contentType, applicationJSON)

	if err := json.NewEncoder(writer).Encode(map[string]interface{}{}); err != nil {
		logJSONError(err)
	}
}

func writeErrorResponse(writer http.ResponseWriter, error error) {
	status := http.StatusInternalServerError
	message := "Internal Server Error"

	if reflect.ValueOf(error).Type().String() == "*utils.Error" {
		status = error.(*utils.Error).Code
		message = error.(*utils.Error).Message
	}

	writer.Header().Set(contentType, applicationJSON)
	writer.WriteHeader(status)

	if err := json.NewEncoder(writer).Encode(map[string]interface{}{"message": message}); err != nil {
		logJSONError(err)
	}
}
