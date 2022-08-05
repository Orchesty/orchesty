export default {
  getInstalledApps: {
    id: 'GET_INSTALLED_APPS',
    request: () => ({
      url: `/application/installed`,
      method: 'GET',
    }),
  },
  getAvailableApps: {
    id: 'GET_AVAILABLE_APPS',
    request: () => ({
      url: `/application/available`,
      method: 'GET',
    }),
  },
  getApp: {
    id: 'GET_APP_DETAIL',
    request: ({ key }) => ({
      url: `/application/${key}`,
      method: 'GET',
    }),
  },
  getAppPreview: {
    id: 'GET_APP_DETAIL_PREVIEW',
    request: ({ key }) => ({
      url: `/application/${key}/preview`,
      method: 'GET',
    }),
  },

  uninstallApp: {
    id: 'UNINSTALL_APP',
    request: ({ key }) => ({
      url: `/application/${key}`,
      method: 'DELETE',
    }),
  },
  installApp: {
    id: 'INSTALL_APP',
    request: ({ key }) => ({
      url: `/application/${key}`,
      method: 'POST',
    }),
  },
  authorizeApp: {
    id: 'AUTHORIZE_APP',
    request: ({ key }) => ({
      url: `/application/${key}/authorize`,
      method: 'GET',
    }),
  },
  saveAppSettings: {
    id: 'SAVE_APP',
    request: ({ key, data }) => ({
      url: `/application/${key}`,
      method: 'PUT',
      data,
    }),
  },
  setPasswordApp: {
    id: 'SET_PASSWORD',
    request: ({ key, data }) => ({
      url: `/application/${key}/set-password`,
      method: 'PUT',
      data,
    }),
  },
}
