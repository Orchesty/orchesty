export default {
  getSettings: {
    id: "GET_SETTINGS_DETAIL",
    request: () => ({
      url: `/settings/`,
      method: "GET",
    }),
  },
  authorizeSettings: {
    id: "AUTHORIZE_SETTINGS",
    request: () => ({
      url: `/settings/authorize`,
      method: "GET",
    }),
  },
  saveSettings: {
    id: "SAVE_SETTINGS",
    request: ({ data }) => ({
      url: `/settings`,
      method: "PUT",
      data,
    }),
  },
  setPasswordSettings: {
    id: "SET_SETTINGS_PASSWORD",
    request: ({ data }) => ({
      url: `/settings/set-password`,
      method: "PUT",
      data,
    }),
  },
}
