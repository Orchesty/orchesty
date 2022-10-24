export default {
  getInstalledApps: {
    id: "GET_INSTALLED_APPS",
    urlPattern: "/application/installed",
    request: () => ({
      url: `/application/installed`,
      method: "GET",
    }),
  },
  getAvailableApps: {
    id: "GET_AVAILABLE_APPS",
    urlPattern: "/application/available",
    request: () => ({
      url: `/application/available`,
      method: "GET",
    }),
  },
  getApp: {
    id: "GET_APP_DETAIL",
    urlPattern: "/application/:key",
    request: ({ key }) => ({
      url: `/application/${key}`,
      method: "GET",
    }),
  },
  getAppPreview: {
    id: "GET_APP_DETAIL_PREVIEW",
    urlPattern: "/application/:key/preview",
    request: ({ key }) => ({
      url: `/application/${key}/preview`,
      method: "GET",
    }),
  },
  uninstallApp: {
    id: "UNINSTALL_APP",
    urlPattern: "/application/:key",
    request: ({ key }) => ({
      url: `/application/${key}`,
      method: "DELETE",
    }),
  },
  installApp: {
    id: "INSTALL_APP",
    urlPattern: "/application/:key",
    request: ({ key }) => ({
      url: `/application/${key}`,
      method: "POST",
    }),
  },
  authorizeApp: {
    id: "AUTHORIZE_APP",
    urlPattern: "/application/:key/authorize",
    request: ({ key }) => ({
      url: `/application/${key}/authorize`,
      method: "GET",
    }),
  },
  saveAppSettings: {
    id: "SAVE_APP",
    urlPattern: "/application/:key",
    request: ({ key, data }) => ({
      url: `/application/${key}`,
      method: "PUT",
      data,
    }),
  },
  setPasswordApp: {
    id: "SET_PASSWORD",
    urlPattern: "/application/:key/set-password",
    request: ({ key, data }) => ({
      url: `/application/${key}/set-password`,
      method: "PUT",
      data,
    }),
  },
  activateApp: {
    id: "ACTIVATE_APP",
    urlPattern: "/application/:key/changeState",
    request: ({ key, data }) => ({
      url: `/application/${key}/changeState`,
      method: "PUT",
      data,
    }),
  },
}
