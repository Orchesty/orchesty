import { FLASH_MESSAGES } from "./types"

export default {
  [FLASH_MESSAGES.ACTIONS.ADD]: ({ commit }, payload) => {
    commit(FLASH_MESSAGES.MUTATIONS.ADD, payload)
  },
  [FLASH_MESSAGES.ACTIONS.REMOVE]: ({ commit }, payload) => {
    commit(FLASH_MESSAGES.MUTATIONS.REMOVE, payload)
  },
}
