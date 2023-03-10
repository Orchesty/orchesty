export const APP_STORE = {
  NAMESPACE: "appStore",
  ACTIONS: {
    UNINSTALL_APP_REQUEST: "UNINSTALL_APP_REQUEST",
    GET_INSTALLED_APP: "GET_INSTALLED_APP",
    GET_AVAILABLE_APP: "GET_AVAILABLE_APP",
    INSTALL_APP_REQUEST: "INSTALL_APP_REQUEST",
    GET_AVAILABLE_APPS: "GET_AVAILABLE_APPS",
    GET_INSTALLED_APPS: "GET_INSTALLED_APPS",
    SAVE_APP_SETTINGS: "SAVE_APP_SETTINGS",
    SUBSCRIBE_WEBHOOK: "SUBSCRIBE_WEBHOOK",
    UNSUBSCRIBE_WEBHOOK: "UNSUBSCRIBE_WEBHOOK",
    APP_SET_PASSWORD: "APP_SET_PASSWORD",
    AUTHORIZE: "AUTHORIZE",
    ACTIVATE: "ACTIVATE",
    RESET: "RESET",
  },
  GETTERS: {
    GET_INSTALLED_APPS: "GET_INSTALLED_APPS",
    GET_AVAILABLE_APPS: "GET_AVAILABLE_APPS",
    GET_ACTIVE_APP: "GET_ACTIVE_APP",
    IS_INSTALLED: "IS_INSTALLED",
  },
  MUTATIONS: {
    GET_APP_RESPONSE: "GET_APP_RESPONSE",
    GET_AVAILABLE_APPS: "GET_AVAILABLE_APPS",
    GET_INSTALLED_APPS: "GET_INSTALLED_APPS",
    RESET: "RESET",
  },
}
