package router

import (
	"encoding/json"
	"github.com/julienschmidt/httprouter"
	"github.com/rs/zerolog/log"
	"limiter/pkg/config"
	"limiter/pkg/mongo"
	"net/http"
	"net/http/httptest"
	"net/http/httputil"
	"net/url"
	"strings"
	"time"
)

type Route struct {
	Method    string
	Pattern   string
	Handler   route
	Protected bool
}

const (
	contentType     = "Content-Type"
	applicationJSON = "application/json; charset=utf-8"
)

type Container struct {
	Mongo mongo.MongoSvc
}

type route func(http.ResponseWriter, *http.Request, httprouter.Params, Container)

func Router(container Container) *httprouter.Router {
	router := httprouter.New()
	options := map[string]bool{}

	for _, route := range routes() {
		r := route // Just keep it there...
		handle := corsHandler(
			func(writer http.ResponseWriter, request *http.Request, params httprouter.Params) {
				r.Handler(writer, request, params, container)
			},
		)

		if config.App.Debug {
			handle = logHandler(handle)
		}
		if route.Protected {
			handle = authorizationHandler(handle, container)
		}
		router.Handle(route.Method, route.Pattern, handle)

		if !options[route.Pattern] {
			options[route.Pattern] = true
			if config.App.Debug {
				router.Handle(http.MethodOptions, route.Pattern, logHandler(corsHandler(nil)))
			} else {
				router.Handle(http.MethodOptions, route.Pattern, nil)
			}
		}
	}

	router.HandleMethodNotAllowed = true

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

func logHandler(handle httprouter.Handle) httprouter.Handle {
	return func(writer http.ResponseWriter, request *http.Request, parameters httprouter.Params) {
		body := ""

		if one, err := httputil.DumpRequest(request, false); err == nil {
			if two, err := httputil.DumpRequest(request, true); err == nil {
				body = strings.Replace(string(two), string(one), "", -1)
			}
		}

		response := httptest.NewRecorder()
		timeOne := time.Now()
		handle(response, request, parameters)
		timeTwo := time.Now()

		for key, value := range response.Header() {
			writer.Header().Set(key, value[0])
		}

		writer.WriteHeader(response.Code)

		if _, err := response.Body.WriteTo(writer); err != nil {
			log.Error().Err(err).Send()
		}

		query, _ := url.QueryUnescape(request.URL.RawQuery)
		duration := timeTwo.Sub(timeOne).Seconds()

		log.Debug().Interface("data", map[string]interface{}{
			"duration":   duration,
			"statusCode": response.Code,
			"method":     request.Method,
			"url":        request.URL.Path,
			"query":      query,
			"body":       body,
		})
	}
}

func response(writer http.ResponseWriter, content interface{}) {
	writer.Header().Set(contentType, applicationJSON)

	if err := json.NewEncoder(writer).Encode(content); err != nil {
		log.Error().Err(err)
	}
}

func authorizationHandler(next httprouter.Handle, container Container) httprouter.Handle {
	apiToken := container.Mongo.GetApiToken()
	return func(writer http.ResponseWriter, request *http.Request, parameters httprouter.Params) {
		if apiToken != "" && request.Header.Get("orchesty-api-key") != apiToken {
			writer.WriteHeader(http.StatusUnauthorized)
			return
		}

		next(writer, request, parameters)
	}
}

func errorResponse(writer http.ResponseWriter, error error) {
	resp := struct {
		Error string `json:"error"`
	}{
		Error: error.Error(),
	}

	writer.WriteHeader(400)
	log.Error().Err(error).Send()
	response(writer, resp)
}
