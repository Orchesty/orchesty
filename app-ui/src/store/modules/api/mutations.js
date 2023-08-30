import { REQUESTS_STATE } from './types'
import createState from './state'
import { resetState } from '../../utils'
import { API } from '../../../api'

export default {
  [REQUESTS_STATE.MUTATIONS.START_SENDING]: (state, { id, errorType, loadingType }) => {
    const items = { ...state.items }

    items[id] = {
      id,
      isSending: true,
      loadingType: loadingType,
      isError: false,
      error: '',
      errorType: errorType,
    }

    state.items = items
  },
  [REQUESTS_STATE.MUTATIONS.STOP_SENDING]: (state, { id }) => {
    const items = { ...state.items }
    if (id !== API.auth.login.id) {
      if (items[id]) {
        items[id].isSending = false
      }
    }

    state.items = items
  },
  [REQUESTS_STATE.MUTATIONS.ADD_ERROR]: (state, { id, error }) => {
    const items = { ...state.items }

    if (items[id]) {
      items[id].isError = true
      items[id].error = error
    }

    state.items = items
  },
  [REQUESTS_STATE.MUTATIONS.REMOVE_ERROR]: (state, { id }) => {
    const items = { ...state.items }

    if (items[id]) {
      items[id].isError = false
      items[id].error = ''
    }

    state.items = items
  },
  [REQUESTS_STATE.MUTATIONS.CLEAR_ERRORS]: (state) => {
    state.items = []
  },
  [REQUESTS_STATE.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
