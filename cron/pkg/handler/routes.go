package handler

import "net/http"

func Routes() []Route {
	return []Route{
		{
			Name:    "Status",
			Method:  http.MethodGet,
			Pattern: "/status",
			Handler: HandleStatus,
		},
		{
			Name:    "CRON Select",
			Method:  http.MethodGet,
			Pattern: "/crons",
			Handler: HandleSelect,
		},
		{
			Name:    "CRON Upsert",
			Method:  http.MethodPatch,
			Pattern: "/crons",
			Handler: HandleUpsert,
		},
		{
			Name:    "CRON Delete",
			Method:  http.MethodDelete,
			Pattern: "/crons",
			Handler: HandleDelete,
		},
	}
}
