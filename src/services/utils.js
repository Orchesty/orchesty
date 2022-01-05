import { withNamespace } from '../store/utils'
import { REQUESTS_STATE } from '../store/modules/api/types'
import { AUTH } from '../store/modules/auth/types'
import { i18n } from '../localization'
import { addErrorMessage } from './flashMessages'
import { STORE } from '../store/types'
import { ROUTES } from '../router/routes'

export const startSending = (commit, id, errorType, loadingType) => {
  commit(withNamespace(REQUESTS_STATE.NAMESPACE, REQUESTS_STATE.MUTATIONS.START_SENDING), {
    id,
    errorType,
    loadingType,
  })
}

export const removeError = (commit, id) => {
  commit(withNamespace(REQUESTS_STATE.NAMESPACE, REQUESTS_STATE.MUTATIONS.REMOVE_ERROR), { id })
}

export const addError = (commit, id, error) => {
  commit(withNamespace(REQUESTS_STATE.NAMESPACE, REQUESTS_STATE.MUTATIONS.ADD_ERROR), {
    id,
    error,
  })
}

export const stopSending = (commit, id) => {
  commit(withNamespace(REQUESTS_STATE.NAMESPACE, REQUESTS_STATE.MUTATIONS.STOP_SENDING), { id })
}

export const onError = (store, id, errorCode, errorType, message) => {
  if (!errorType) {
    addErrorMessage(store.dispatch, id, message)
  }

  addError(store.commit, id, i18n.t(message))
}

export const logout = async (commit, dispatch) => {
  commit(withNamespace(AUTH.NAMESPACE, AUTH.MUTATIONS.LOGOUT_RESPONSE), null, { root: true })
  await dispatch(STORE.ACTIONS.ROUTER_PUSH, { name: ROUTES.LOGIN }, { root: true })
  dispatch(STORE.ACTIONS.RESET, null, { root: true })
}
