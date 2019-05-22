package router

// GetDefaultRoutes returns collection of default routes
func GetDefaultRoutes() Routes {
	return Routes{
		Route{
			Name:        "Status",
			Method:      "GET",
			Pattern:     "/status",
			HandlerFunc: HandleClear(HandleStatus),
		},
		Route{
			Name:        "Run topology by ID",
			Method:      "POST",
			Pattern:     "/topologies/{topology}/nodes/{node}/run",
			HandlerFunc: HandleClear(HandleRunByID),
		},
		Route{
			Name:        "Run topology by name",
			Method:      "POST",
			Pattern:     "/topologies/{topology}/nodes/{node}/run-by-name",
			HandlerFunc: HandleClear(HandleRunByName),
		},
		Route{
			Name:        "Run topology by application",
			Method:      "POST",
			Pattern:     "/topologies/{topology}/nodes/{node}/token/{token}/run",
			HandlerFunc: HandleClear(HandleRunByApplication),
		},
		Route{
			Name:        "Run human task topology by ID",
			Method:      "POST",
			Pattern:     "/human-task/topologies/{topology}/nodes/{node}/run",
			HandlerFunc: HandleClear(HandleHumanTaskRunByID),
		},
		Route{
			Name:        "Run human task topology by ID with token",
			Method:      "POST",
			Pattern:     "/human-task/topologies/{topology}/nodes/{node}/token/{token}/run",
			HandlerFunc: HandleClear(HandleHumanTaskRunByID),
		},
		Route{
			Name:        "Run human task topology by name",
			Method:      "POST",
			Pattern:     "/human-task/topologies/{topology}/nodes/{node}/run-by-name",
			HandlerFunc: HandleClear(HandleHumanTaskRunByName),
		},
		Route{
			Name:        "Run human task topology by name with token",
			Method:      "POST",
			Pattern:     "/human-task/topologies/{topology}/nodes/{node}/token/{token}/run-by-name",
			HandlerFunc: HandleClear(HandleHumanTaskRunByName),
		},
		Route{
			Name:        "Stop human task topology by ID",
			Method:      "POST",
			Pattern:     "/human-task/topologies/{topology}/nodes/{node}/stop",
			HandlerFunc: HandleClear(HandleHumanTaskStopByID),
		},
		Route{
			Name:        "Stop human task topology by ID with token",
			Method:      "POST",
			Pattern:     "/human-task/topologies/{topology}/nodes/{node}/token/{token}/stop",
			HandlerFunc: HandleClear(HandleHumanTaskStopByID),
		},
		Route{
			Name:        "Stop human task topology by name",
			Method:      "POST",
			Pattern:     "/human-task/topologies/{topology}/nodes/{node}/stop-by-name",
			HandlerFunc: HandleClear(HandleHumanTaskStopByName),
		},
		Route{
			Name:        "Stop human task topology by name with token",
			Method:      "POST",
			Pattern:     "/human-task/topologies/{topology}/nodes/{node}/token/{token}/stop-by-name",
			HandlerFunc: HandleClear(HandleHumanTaskStopByName),
		},
		Route{
			Name:        "Invalidate topology cache",
			Method:      "POST",
			Pattern:     "/topologies/{topology}/invalidate-cache",
			HandlerFunc: HandleClear(HandleInvalidateCache),
		},
	}
}
