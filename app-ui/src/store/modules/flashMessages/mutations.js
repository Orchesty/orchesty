import { FLASH_MESSAGES } from "./types"
import createState from "./state"
import { resetState } from "../../utils"

export default {
  [FLASH_MESSAGES.MUTATIONS.ADD]: (state, payload) => {
    let id = 0
    if (state.flashMessages.length > 2) {
      state.flashMessages.shift()
      id = state.flashMessages[state.flashMessages.length - 1].id + 1
    } else {
      id = state.flashMessages.length
    }

    state.flashMessages.push({ id, ...payload })
  },
  [FLASH_MESSAGES.MUTATIONS.REMOVE]: (state, payload) => {
    state.flashMessages = state.flashMessages.filter(
      (item) => item.id === payload
    )
  },
  [FLASH_MESSAGES.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
