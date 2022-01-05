import { APP_STORE } from './types'
import createState from './state'
import { resetState } from '../../utils'

export default {
  [APP_STORE.MUTATIONS.GET_APP_RESPONSE]: (state, data) => {
    state.app = data
  },
  [APP_STORE.MUTATIONS.GET_AVAILABLE_APPS]: (state, data) => {
    state.available = data.items
  },
  [APP_STORE.MUTATIONS.GET_INSTALLED_APPS]: (state, data) => {
    state.installed = data.items
  },
  [APP_STORE.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
