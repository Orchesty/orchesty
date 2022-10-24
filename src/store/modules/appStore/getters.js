import { APP_STORE } from "@/store/modules/appStore/types"

export default {
  [APP_STORE.GETTERS.GET_INSTALLED_APPS]: (state) => {
    return state.appsInstalled
  },
  [APP_STORE.GETTERS.GET_AVAILABLE_APPS]: (state) => {
    return state.appsAvailable
  },
  [APP_STORE.GETTERS.GET_ACTIVE_APP]: (state) => {
    return state.appActive
  },
  [APP_STORE.GETTERS.IS_INSTALLED]: (state) => (key) => {
    return state.appsInstalled.some((installedApp) => installedApp.key === key)
  },
}
