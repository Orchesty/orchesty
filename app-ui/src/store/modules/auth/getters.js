import { AUTH } from "./types"

export default {
  [AUTH.GETTERS.IS_LOGGED]: (state) => {
    return state.token !== null
  },
  [AUTH.GETTERS.IS_CHECKED]: (state) => {
    return state.checked
  },
  [AUTH.GETTERS.GET_LOGGED_USER]: (state) => {
    return state.user
  },
  [AUTH.GETTERS.GET_LOGGED_USER_ID]: (state) => {
    return state.id
  },
  [AUTH.GETTERS.GET_TOKEN]: (state) => {
    return state.token
  },
}
