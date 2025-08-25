import { APP_STORE } from "./types"

export default {
  [APP_STORE.MUTATIONS.GET_SDK]: (state, data) => {
    state.sdk = data
  },
}
