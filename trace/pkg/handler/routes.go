package handler

import "net/http"

func Routes() []Route {
	return []Route{
		{
			Name:    "Status",
			Pattern: http.MethodGet + " /status",
			Handler: HandleStatus,
		},
		{
			Name:    "Trace",
			Pattern: http.MethodGet + " /trace",
			Handler: HandleTrace,
		},
	}
}
