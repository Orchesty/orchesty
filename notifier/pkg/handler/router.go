package handler

import (
	"encoding/json"
	"net/http"
	"reflect"
	"strings"

	"notifier/pkg/config"
	"notifier/pkg/utils"

	log "github.com/hanaboso/go-log/pkg"
)

const (
	contentType     = "Content-Type"
	applicationJson = "application/json; charset=utf-8"
)

type Route struct {
	Name    string
	Pattern string
	Handler http.HandlerFunc
}

func Router(routes []Route) *http.ServeMux {
	mux := http.NewServeMux()
	options := map[string]bool{}

	for _, route := range routes {
		mux.HandleFunc(route.Pattern, corsHandler(route.Handler))

		path := extractPath(route.Pattern)
		if !options[path] {
			options[path] = true
			mux.HandleFunc("OPTIONS "+path, corsHandler(nil))
		}
	}

	return mux
}

func extractPath(pattern string) string {
	parts := strings.SplitN(pattern, " ", 2)
	if len(parts) == 2 {
		return parts[1]
	}

	return pattern
}

func corsHandler(handle http.HandlerFunc) http.HandlerFunc {
	return func(writer http.ResponseWriter, request *http.Request) {
		if origin := request.Header.Get("Origin"); origin != "" {
			writer.Header().Set("Access-Control-Allow-Origin", origin)
		} else {
			writer.Header().Set("Access-Control-Allow-Origin", "*")
		}

		writer.Header().Set("Access-Control-Allow-Methods", "GET, PUT, OPTIONS")
		writer.Header().Set("Access-Control-Allow-Headers", "Content-Type, X-Tenant-Id, X-User-Id")
		writer.Header().Set("Access-Control-Allow-Credentials", "true")
		writer.Header().Set("Access-Control-Max-Age", "3600")

		if request.Method == http.MethodOptions {
			writer.WriteHeader(http.StatusNoContent)

			return
		}

		handle(writer, request)
	}
}

func writeResponse(writer http.ResponseWriter, content interface{}) {
	writer.Header().Set(contentType, applicationJson)

	if err := json.NewEncoder(writer).Encode(content); err != nil {
		logContext().Error(err)
	}
}

func writeErrorResponse(writer http.ResponseWriter, err error) {
	status := http.StatusInternalServerError
	message := "Internal Server Error"

	if reflect.ValueOf(err).Type().String() == "*utils.Error" {
		status = err.(*utils.Error).Code
		message = err.(*utils.Error).Message
	}

	writer.Header().Set(contentType, applicationJson)
	writer.WriteHeader(status)

	if err := json.NewEncoder(writer).Encode(map[string]interface{}{"message": message}); err != nil {
		logContext().Error(err)
	}
}

func logContext() log.Logger {
	return config.Logger.WithFields(map[string]interface{}{
		"service": "NOTIFIER",
		"type":    "Handler",
	})
}
