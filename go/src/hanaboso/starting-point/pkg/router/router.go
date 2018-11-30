package api

import (
	"encoding/json"
	"github.com/gorilla/mux"
	"net/http"
)

// Route route
type Route struct {
	Name        string
	Method      string
	Pattern     string
	HandlerFunc http.HandlerFunc
}

// Routes routes
type Routes []Route

// Router router
func Router(routes Routes) *mux.Router {
	router := mux.NewRouter().StrictSlash(true)

	for _, route := range routes {
		router.Methods(route.Method).Path(route.Pattern).Name(route.Name).Handler(route.HandlerFunc)
	}

	router.NotFoundHandler = http.HandlerFunc(notFoundHandler)
	router.MethodNotAllowedHandler = http.HandlerFunc(methodNotAllowedHandler)

	return router
}

// HandleStatus checks if HTTP is working correctly
func HandleStatus(w http.ResponseWriter, r *http.Request) {
	_ = json.NewEncoder(writeResponseHeaders(w)).Encode(map[string]string{"status": "OK"})
}

func notFoundHandler(w http.ResponseWriter, r *http.Request) {
	writeResponseHeaders(w)
	w.WriteHeader(http.StatusNotFound)
}

func methodNotAllowedHandler(w http.ResponseWriter, r *http.Request) {
	writeResponseHeaders(w)
	w.WriteHeader(http.StatusMethodNotAllowed)
}

func writeResponseHeaders(w http.ResponseWriter) http.ResponseWriter {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")

	return w
}
