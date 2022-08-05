export default {
  router: {
    baseUrl: process.env.VUE_APP_FRONTEND_URL || '/',
  },
  backend: {
    apiBaseUrl: process.env.VUE_APP_BACKEND_URL || 'http://127.0.0.1:80',
    apiStartingPoint:
      process.env.VUE_APP_STARTINGPONT_URL || 'http://127.0.0.66:82',
  },
  authBacklink: '/not-logged-in',
  checkLogged: {
    refreshTime: 30,
  },
}
