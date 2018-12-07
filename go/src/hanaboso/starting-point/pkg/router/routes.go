package router

// GetDefaultRoutes returns collection of default routes
func GetDefaultRoutes() Routes {
	return Routes{
		Route{
			Name:        "Status",
			Method:      "GET",
			Pattern:     "/starting-point/status",
			HandlerFunc: HandleClear(HandleStatus),
		},
		Route{
			Name:        "Run topology by ID",
			Method:      "POST",
			Pattern:     "/starting-point/topologies/{topology}/nodes/{node}/run",
			HandlerFunc: HandleClear(HandleRunByID),
		},
		Route{
			Name:        "Run topology by name",
			Method:      "POST",
			Pattern:     "/starting-point/topologies/{topology}/nodes/{node}/run-by-name",
			HandlerFunc: HandleClear(HandleRunByName),
		},
		Route{
			Name:        "Run human task topology by ID",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/run",
			HandlerFunc: HandleClear(HandleHumanTaskRunByID),
		},
		Route{
			Name:        "Run human task topology by ID with token",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/token/{token}/run",
			HandlerFunc: HandleClear(HandleHumanTaskRunByID),
		},
		Route{
			Name:        "Run human task topology by name",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/run-by-name",
			HandlerFunc: HandleClear(HandleHumanTaskRunByName),
		},
		Route{
			Name:        "Run human task topology by name with token",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/token/{token}/run-by-name",
			HandlerFunc: HandleClear(HandleHumanTaskRunByName),
		},
		Route{
			Name:        "Stop human task topology by ID",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/stop",
			HandlerFunc: HandleClear(HandleHumanTaskStopByID),
		},
		Route{
			Name:        "Stop human task topology by ID with token",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/token/{token}/stop",
			HandlerFunc: HandleClear(HandleHumanTaskStopByID),
		},
		Route{
			Name:        "Stop human task topology by name",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/stop-by-name",
			HandlerFunc: HandleClear(HandleHumanTaskStopByName),
		},
		Route{
			Name:        "Stop human task topology by name with token",
			Method:      "POST",
			Pattern:     "/starting-point/human-task/topologies/{topology}/nodes/{node}/token/{token}/stop-by-name",
			HandlerFunc: HandleClear(HandleHumanTaskStopByName),
		},
		Route{
			Name:        "Invalidate topology cache",
			Method:      "POST",
			Pattern:     "/starting-point/topologies/{topology}/invalidate-cache",
			HandlerFunc: HandleClear(HandleInvalidateCache),
		},
	}
}
