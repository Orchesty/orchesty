package router

import (
	"encoding/json"
	"hanaboso/topologygenerator/log"
	"hanaboso/topologygenerator/model"
	"hanaboso/topologygenerator/response"

	"net/http"
	"reflect"
	"time"

	"github.com/gorilla/mux"
	"fmt"
)

type Route struct {
	Name        string
	Method      string
	Pattern     string
	HandlerFunc http.HandlerFunc
}

type Routes []Route

func Router(routes Routes) *mux.Router {

	router := mux.NewRouter().StrictSlash(true)
	for _, route := range routes {
		var handler http.Handler

		handler = route.HandlerFunc
		handler = model.Logger(handler, route.Name)
		handler = panicHandler(handler)

		router.
			Methods(route.Method).
			Path(route.Pattern).
			Name(route.Name).
			Handler(handler)
	}

	router.NotFoundHandler = http.HandlerFunc(notFoundHandler)
	router.MethodNotAllowedHandler = http.HandlerFunc(notAllowHandler)

	return router
}

func panicHandler(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {

		log.Info("Panic Handler called.")

		defer func() {
			if r := recover(); r != nil {

				var message string

				if reflect.TypeOf(r) == reflect.TypeOf(model.AppError{}) {
					message = r.(model.AppError).Message
				} else {
					message = r.(error).Error()
				}

				fmt.Println(message)
				log.Infof("PanicHandler: %s", message)

				r := response.RequestResponse{Message: message, DockerInfo: nil}

				respBody, err := json.MarshalIndent(r, "", "  ")
				if err != nil {
					log.Fatal(err)
					// os.Exit(1) // TODO: Really exit?
				}

				response.ResponseWithJSON(w, respBody, http.StatusInternalServerError)
			}
		}()

		next.ServeHTTP(w, r)
	})
}

func notFoundHandler(w http.ResponseWriter, r *http.Request) {

	model.LogRequest(r, "notFoundHandler", time.Now(), http.StatusNotFound)
	message := response.RequestResponse{Message: "Page not found"}
	response.ResponseWithJSON(w, message.Prepare(), http.StatusNotFound)
}

func notAllowHandler(w http.ResponseWriter, r *http.Request) {
	model.LogRequest(r, "notAllowHandler", time.Now(), http.StatusMethodNotAllowed)
	message := response.RequestResponse{Message: "Method not allowed"}
	response.ResponseWithJSON(w, message.Prepare(), http.StatusMethodNotAllowed)
}
