package router

import "fmt"

const (
	GET    = "GET"
	POST   = "POST"
	PUT    = "PUT"
	PATCH  = "PATCH"
	DELETE = "DELETE"
)

func routes() []Route {
	routeList := map[string][]Route{
		"": {
			{
				Method:  DELETE,
				Pattern: "/close",
				Handler: Close,
			},
		},
		"/api": {
			{
				Method:    POST,
				Pattern:   "/process",
				Handler:   Process,
				Protected: true,
			},
			{
				Method:    DELETE,
				Pattern:   "/destroy",
				Handler:   Destroy,
				Protected: true,
			},
		},
	}

	return prefixRoutes(routeList)
}

func prefixRoutes(routeList map[string][]Route) []Route {
	var routes []Route
	for ver, routeList := range routeList {
		for _, route := range routeList {
			route.Pattern = fmt.Sprintf("%s%s", ver, route.Pattern)
			routes = append(routes, route)
		}
	}

	return routes
}
