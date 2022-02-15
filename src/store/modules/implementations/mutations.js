import { IMPLEMENTATIONS } from './types'
import createState from './state'
import { resetState } from '../../utils'

export default {
  [IMPLEMENTATIONS.MUTATIONS.LIST_IMPLEMENTATIONS]: (state, data) => {
    localStorage.setItem('pipes', JSON.stringify(data) || null)
    state.topologyImportState.implementationsProject = data.items
  },
  [IMPLEMENTATIONS.MUTATIONS.GET_IMPLEMENTATION_RESPONSE]: (state, data) => {
    state.implementations = data
  },
  [IMPLEMENTATIONS.MUTATIONS.SET_FILE_IMPLEMENTATIONS]: (state, data) => {
    state.topologyImportState.implementationsFile = data
  },
  [IMPLEMENTATIONS.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
