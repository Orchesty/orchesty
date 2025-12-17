package router

import (
	"fmt"
	"limiter/pkg/enum"
)

const (
	GET    = "GET"
	POST   = "POST"
	PUT    = "PUT"
	PATCH  = "PATCH"
	DELETE = "DELETE"
)

func routes() []Route {
	routeList := map[string][]Route{
		"/terminate": {
			{
				Method:    DELETE,
				Pattern:   "/correlation-id/:key",
				Handler:   Terminate(fmt.Sprintf("message.headers.%s", enum.Header_CorrelationId)),
				Protected: true,
			},
			{
				Method:    DELETE,
				Pattern:   "/limit-key/:key",
				Handler:   Terminate("limitKey"),
				Protected: true,
			},
			{
				Method:    DELETE,
				Pattern:   "/topology-id/:key",
				Handler:   Terminate(fmt.Sprintf("message.headers.%s", enum.Header_TopologyId)),
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
