export default {
  getSettings: {
    id: "GET_SETTINGS_DETAIL",
    request: ({ sdk }) => ({
      url: `/settings?sdk=${sdk}`,
      method: "GET",
    }),
  },
  authorizeSettings: {
    id: "AUTHORIZE_SETTINGS",
    request: ({ sdk }) => ({
      url: `/settings/authorize?sdk=${sdk}`,
      method: "GET",
    }),
  },
  saveSettings: {
    id: "SAVE_SETTINGS",
    request: ({ sdk, data }) => ({
      url: `/settings?sdk=${sdk}`,
      method: "PUT",
      data,
    }),
  },
  setPasswordSettings: {
    id: "SET_SETTINGS_PASSWORD",
    request: ({ sdk, data }) => ({
      url: `/settings/set-password?sdk=${sdk}`,
      method: "PUT",
      data,
    }),
  },
}
