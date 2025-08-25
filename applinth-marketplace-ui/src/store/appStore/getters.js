import { APP_STORE } from "./types"

export default {
  [APP_STORE.GETTERS.GET_SDK]: (state) => {
    return state.sdk
  },
}
