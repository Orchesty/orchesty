package router

// Routes returns application HTTP routes
func Routes() []Route {
	return []Route{
		{
			Name:    "Status",
			Method:  "GET",
			Pattern: "/status",
			Handler: HandleStatus,
		},
		{
			Name:    "CRON Get",
			Method:  "GET",
			Pattern: "/crons",
			Handler: HandleGetAll,
		},
		{
			Name:    "CRON Create",
			Method:  "POST",
			Pattern: "/crons",
			Handler: HandleCreate,
		},
		{
			Name:    "CRON Update",
			Method:  "PUT",
			Pattern: "/crons/:topology/:node",
			Handler: HandleUpdate,
		},
		{
			Name:    "CRON Upsert",
			Method:  "PATCH",
			Pattern: "/crons/:topology/:node",
			Handler: HandleUpsert,
		},
		{
			Name:    "CRON Delete",
			Method:  "DELETE",
			Pattern: "/crons/:topology/:node",
			Handler: HandleDelete,
		},
		{
			Name:    "CRON Batch Create",
			Method:  "POST",
			Pattern: "/crons-batches",
			Handler: HandleBatchCreate,
		},
		{
			Name:    "CRON Batch Update",
			Method:  "PUT",
			Pattern: "/crons-batches",
			Handler: HandleBatchUpdate,
		},
		{
			Name:    "CRON Batch Upsert",
			Method:  "PATCH",
			Pattern: "/crons-batches",
			Handler: HandleBatchUpsert,
		},
		{
			Name:    "CRON Batch Delete",
			Method:  "DELETE",
			Pattern: "/crons-batches",
			Handler: HandleBatchDelete,
		},
	}
}
