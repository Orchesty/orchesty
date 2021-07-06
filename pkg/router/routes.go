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
				Method:  GET,
				Pattern: "/status",
				Handler: Status,
			},
			{
				Method:  DELETE,
				Pattern: "/clear",
				Handler: Clear,
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
