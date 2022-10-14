export default {
  getInstalledApps: {
    id: 'APP_STORE_LIST_INSTALLED_APPS',
    request: () => ({
      url: `/applications/installed`,
      method: 'GET',
    }),
  },
  getAvailableApps: {
    id: 'APP_STORE_LIST_AVAILABLE_APPS',
    request: () => ({
      url: `/applications/available`,
      method: 'GET',
    }),
  },
  getInstalledApp: {
    id: 'APP_STORE_GET_INSTALLED_APP',
    request: ({ key }) => ({
      url: `/applications/${key}`,
      method: 'GET',
    }),
  },
  getAvailableApp: {
    id: 'APP_STORE_GET_AVAILABLE_APP',
    request: (key) => ({
      url: `/applications/${key}/preview`,
      method: 'GET',
    }),
  },
  installApp: {
    id: 'APP_STORE_INSTALL_APP',
    request: ({ key }) => ({
      url: `/applications/${key}`,
      method: 'POST',
      data: {},
    }),
  },
  uninstallApp: {
    id: 'APP_STORE_UNINSTALL_APP',
    request: ({ key }) => ({
      url: `/applications/${key}`,
      method: 'DELETE',
    }),
  },
  saveSettings: {
    id: 'APP_STORE_SAVE_SETTINGS',
    request: ({ key, data }) => ({
      url: `/applications/${key}`,
      method: 'PUT',
      data,
    }),
  },
  subscribeToWebhook: {
    id: 'APP_STORE_WEBHOOK_SUBSCRIBE',
    request: ({ key, data }) => ({
      url: `/webhook/applications/${key}/subscribe`,
      method: 'POST',
      data,
    }),
  },
  setPasswordApp: {
    id: 'APP_SET_PASSWORD',
    request: ({ key, data }) => ({
      url: `/applications/${key}/password`,
      method: 'PUT',
      data,
    }),
  },
  activateApp: {
    id: 'ACTIVATE_APP',
    request: ({ key, data }) => ({
      url: `/applications/${key}/changeState`,
      method: 'PUT',
      data,
    }),
  },
}
