export default {
  getInstalledApps: {
    id: 'APP_STORE_LIST_INSTALLED_APPS',
    request: ({ params }) => ({
      url: `/applications/users/${params.id}`,
      method: 'GET',
    }),
  },
  getAvailableApps: {
    id: 'APP_STORE_LIST_AVAILABLE_APPS',
    request: ({ filter, paging, sorter, search }) => ({
      url: `/applications?filter=${JSON.stringify({ filter, paging, sorter, search })}`,
      method: 'GET',
    }),
  },
  getInstalledApp: {
    id: 'APP_STORE_GET_INSTALLED_APP',
    request: ({ key, userId }) => ({
      url: `/applications/${key}/users/${userId}`,
      method: 'GET',
    }),
  },
  getAvailableApp: {
    id: 'APP_STORE_GET_AVAILABLE_APP',
    request: ({ key }) => ({
      url: `/applications/${key}`,
      method: 'GET',
    }),
  },
  installApp: {
    id: 'APP_STORE_INSTALL_APP',
    request: ({ key, userId }) => ({
      url: `/applications/${key}/users/${userId}`,
      method: 'POST',
      data: {},
    }),
  },
  uninstallApp: {
    id: 'APP_STORE_UNINSTALL_APP',
    request: ({ key, userId }) => ({
      url: `/applications/${key}/users/${userId}`,
      method: 'DELETE',
    }),
  },
  saveSettings: {
    id: 'APP_STORE_SAVE_SETTINGS',
    request: ({ key, userId, data }) => ({
      url: `/applications/${key}/users/${userId}`,
      method: 'PUT',
      data,
    }),
  },
  subscribeToWebhook: {
    id: 'APP_STORE_WEBHOOK_SUBSCRIBE',
    request: ({ key, userId, data }) => ({
      url: `/webhook/applications/${key}/users/${userId}/subscribe`,
      method: 'POST',
      data,
    }),
  },
  setPasswordApp: {
    id: 'APP_SET_PASSWORD',
    request: ({ key, userId, data }) => ({
      url: `/applications/${key}/users/${userId}/password`,
      method: 'PUT',
      data,
    }),
  },
}
