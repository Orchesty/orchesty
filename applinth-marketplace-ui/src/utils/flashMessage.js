import store from "@/store"
import { FLASH_MESSAGES } from "@/store/flashMessages/types"

const showFlashMessage = (message, type) => {
  store.dispatch(`${FLASH_MESSAGES.NAMESPACE}/${FLASH_MESSAGES.ACTIONS.ADD}`, {
    message,
    type,
  })
}

export default showFlashMessage
