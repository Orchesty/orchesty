import { ADMIN_USERS } from "./types"
import createState from "./state"
import { resetState } from "../../utils"

export default {
  [ADMIN_USERS.MUTATIONS.GET_USER_RESPONSE]: (state, data) => {
    state.user = data
  },
  [ADMIN_USERS.MUTATIONS.UPDATE_USER_RESPONSE]: (state, data) => {
    state.settings = data
  },
  [ADMIN_USERS.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
