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
			Name:        "Run topology by ID with user",
			Method:      "POST",
			Pattern:     "/topologies/{topology}/nodes/{node}/user/{user}/run",
			HandlerFunc: HandleClear(HandleRunByID),
		},
		Route{
			Name:        "Run topology by name",
			Method:      "POST",
			Pattern:     "/topologies/{topology}/nodes/{node}/run-by-name",
			HandlerFunc: HandleClear(HandleRunByName),
		},
		Route{
			Name:        "Run topology by name with user",
			Method:      "POST",
			Pattern:     "/topologies/{topology}/nodes/{node}/user/{user}/run-by-name",
			HandlerFunc: HandleClear(HandleRunByName),
		},
		Route{
			Name:        "Run topology by application",
			Method:      "POST",
			Pattern:     "/topologies/{topology}/nodes/{node}/token/{token}/run",
			HandlerFunc: HandleClear(HandleRunByApplication),
		},
		Route{
			Name:        "Invalidate topology cache",
			Method:      "POST",
			Pattern:     "/topologies/{topology}/invalidate-cache",
			HandlerFunc: HandleClear(HandleInvalidateCache),
		},
	}
}
