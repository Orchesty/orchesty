package handler

import (
	"detector/pkg/config"
	"github.com/julienschmidt/httprouter"
	"net/http"
)

const (
	contentType = "Content-Type"
	textPlain   = "text/plain; charset=utf-8"
)

type Route struct {
	Name    string
	Method  string
	Pattern string
	Handler httprouter.Handle
}

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
	writer.Header().Set(contentType, textPlain)
	writer.WriteHeader(http.StatusNotFound)
}

func methodNotAllowedHandler(writer http.ResponseWriter, _ *http.Request) {
	writer.Header().Set(contentType, textPlain)
	writer.WriteHeader(http.StatusMethodNotAllowed)
}

func writeResponse(writer http.ResponseWriter, content string) {
	writer.Header().Set(contentType, textPlain)

	if _, err := writer.Write([]byte(content)); err != nil {
		config.Logger.Error(err)
	}
}
