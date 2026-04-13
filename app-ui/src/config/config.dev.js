export default {
  router: {
    baseUrl: import.meta.env.VITE_FRONTEND_URL || "/",
  },
  backend: {
    apiBaseUrl: import.meta.env.VITE_BACKEND_URL || "http://localhost:80",
    apiStartingPoint:
      import.meta.env.VITE_STARTING_POINT_URL || "http://127.0.0.66:82",
  },
  checkLogged: {
    refreshTime: 30,
  },
}
