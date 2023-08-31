import { withNamespace, dispatchRoot } from "../../store/utils"
import {
  FLASH_MESSAGES_TYPES,
  FLASH_MESSAGES,
} from "../../store/modules/flashMessages/types"
import { i18n } from "../../localization"

export const addSuccessMessage = (dispatch, id, message, messageData = []) => {
  dispatchRoot(
    dispatch,
    withNamespace(FLASH_MESSAGES.NAMESPACE, FLASH_MESSAGES.ACTIONS.ADD),
    {
      id,
      message: i18n.t(message, messageData),
      type: FLASH_MESSAGES_TYPES.SUCCESS,
    }
  )
}

export const addErrorMessage = (dispatch, id, message, messageData = []) => {
  dispatchRoot(
    dispatch,
    withNamespace(FLASH_MESSAGES.NAMESPACE, FLASH_MESSAGES.ACTIONS.ADD),
    {
      id,
      message: i18n.t(message, messageData),
      type: FLASH_MESSAGES_TYPES.ERROR,
    }
  )
}

export const addInfoMessage = (dispatch, id, message, messageData = []) => {
  dispatchRoot(
    dispatch,
    withNamespace(FLASH_MESSAGES.NAMESPACE, FLASH_MESSAGES.ACTIONS.ADD),
    {
      id,
      message: i18n.t(message, messageData),
      type: FLASH_MESSAGES_TYPES.INFO,
    }
  )
}
