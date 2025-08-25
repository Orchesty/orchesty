import { APP_STORE } from "./types"

export default {
  [APP_STORE.ACTIONS.GET_SDK]: async ({ commit }, payload) => {
    commit(APP_STORE.MUTATIONS.GET_SDK, payload)
  },
}
