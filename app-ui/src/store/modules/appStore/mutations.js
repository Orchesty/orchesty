import { APP_STORE } from "./types"
import createState from "./state"
import { resetState } from "../../utils"

export default {
  [APP_STORE.MUTATIONS.GET_APP_RESPONSE]: (state, data) => {
    state.appActive = data
  },
  [APP_STORE.MUTATIONS.GET_AVAILABLE_APPS]: (state, data) => {
    state.appsAvailable = data.items
  },
  [APP_STORE.MUTATIONS.GET_INSTALLED_APPS]: (state, data) => {
    state.appsInstalled = data.items
  },
  [APP_STORE.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
