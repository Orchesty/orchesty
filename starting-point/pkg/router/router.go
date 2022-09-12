package router

import (
	"encoding/json"
	"net/http"
	"starting-point/pkg/config"

	"github.com/gorilla/mux"
)

// Route route
type Route struct {
	Name        string
	Method      string
	Pattern     string
	Protected   bool
	HandlerFunc http.HandlerFunc
}

// Routes routes
type Routes []Route

// Router router
func Router(routes Routes) *mux.Router {
	router := mux.NewRouter().StrictSlash(true)

	if routes == nil {
		routes = GetDefaultRoutes()
	}

	for _, route := range routes {
		if route.Protected {
			router.
				Methods(route.Method, http.MethodOptions).
				Path(route.Pattern).
				Name(route.Name).
				Handler(authorizationHandler(route.HandlerFunc))
		} else {
			router.
				Methods(route.Method, http.MethodOptions).
				Path(route.Pattern).
				Name(route.Name).
				Handler(route.HandlerFunc)
		}
	}

	router.NotFoundHandler = http.HandlerFunc(notFoundHandler)
	router.MethodNotAllowedHandler = http.HandlerFunc(methodNotAllowedHandler)

	return router
}

func authorizationHandler(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if apiKey := r.Header.Get("orchesty-api-key"); config.Config.ApiKey != "" && apiKey != config.Config.ApiKey {
			w.WriteHeader(http.StatusUnauthorized)
			return
		}

		next.ServeHTTP(w, r)
	})
}

func notFoundHandler(w http.ResponseWriter, _ *http.Request) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(http.StatusNotFound)
}

func methodNotAllowedHandler(w http.ResponseWriter, _ *http.Request) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(http.StatusMethodNotAllowed)
}

func writeResponse(w http.ResponseWriter, content map[string]interface{}) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")

	_ = json.NewEncoder(w).Encode(content)
}

func writeErrorResponse(w http.ResponseWriter, status int, content string) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(status)

	_ = json.NewEncoder(w).Encode(map[string]interface{}{"message": content})
}
