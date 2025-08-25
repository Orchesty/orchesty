export default {
  getInstalledApps: {
    id: "GET_INSTALLED_APPS",
    urlPattern: "/application/installed",
    request: ({ sdk }) => ({
      url: `/application/installed?sdk=${sdk}`,
      method: "GET",
    }),
  },
  getAvailableApps: {
    id: "GET_AVAILABLE_APPS",
    urlPattern: "/application/available",
    request: ({ sdk }) => ({
      url: `/application/available?sdk=${sdk}`,
      method: "GET",
    }),
  },
  getApp: {
    id: "GET_APP_DETAIL",
    urlPattern: "/application/:key",
    request: ({ key, sdk }) => ({
      url: `/application/${key}?sdk=${sdk}`,
      method: "GET",
    }),
  },
  getAppPreview: {
    id: "GET_APP_DETAIL_PREVIEW",
    urlPattern: "/application/:key/preview",
    request: ({ key, sdk }) => ({
      url: `/application/${key}/preview?sdk=${sdk}`,
      method: "GET",
    }),
  },
  uninstallApp: {
    id: "UNINSTALL_APP",
    urlPattern: "/application/:key",
    request: ({ key, sdk }) => ({
      url: `/application/${key}?sdk=${sdk}`,
      method: "DELETE",
    }),
  },
  installApp: {
    id: "INSTALL_APP",
    urlPattern: "/application/:key",
    request: ({ key, sdk }) => ({
      url: `/application/${key}?sdk=${sdk}`,
      method: "POST",
    }),
  },
  authorizeApp: {
    id: "AUTHORIZE_APP",
    urlPattern: "/application/:key/authorize",
    request: ({ key, sdk }) => ({
      url: `/application/${key}/authorize?sdk=${sdk}`,
      method: "GET",
    }),
  },
  saveAppSettings: {
    id: "SAVE_APP",
    urlPattern: "/application/:key",
    request: ({ key, sdk, data }) => ({
      url: `/application/${key}?sdk=${sdk}`,
      method: "PUT",
      data,
    }),
  },
  setPasswordApp: {
    id: "SET_PASSWORD",
    urlPattern: "/application/:key/set-password",
    request: ({ key, sdk, data }) => ({
      url: `/application/${key}/set-password?sdk=${sdk}`,
      method: "PUT",
      data,
    }),
  },
  activateApp: {
    id: "ACTIVATE_APP",
    urlPattern: "/application/:key/changeState",
    request: ({ key, sdk, data }) => ({
      url: `/application/${key}/changeState?sdk=${sdk}`,
      method: "PUT",
      data,
    }),
  },
}
