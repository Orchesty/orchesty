package handler

import (
	"net/http"
)

func Routes() []Route {
	return []Route{
		{
			Name:    "Metrics",
			Method:  http.MethodGet,
			Pattern: "/metrics",
			Handler: HandleMetrics,
		},
	}
}
