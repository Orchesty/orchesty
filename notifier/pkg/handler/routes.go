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
			Name:    "List Subscriptions",
			Pattern: http.MethodGet + " /api/subscriptions",
			Handler: HandleListSubscriptions,
		},
		{
			Name:    "Upsert Subscription",
			Pattern: http.MethodPut + " /api/subscriptions",
			Handler: HandleUpsertSubscription,
		},
	}
}
