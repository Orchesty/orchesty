import { GRID } from './types'
import { resetState } from '../../utils'
import createState from './state'
import { prepareGridHeaderStateForSave, createTableKey, createHeaderKey, prepareGridStateForSave } from '../utils'

export default {
  [GRID.MUTATIONS.GRID_RESPONSE]: (state, payload) => {
    state.filter = payload.filter
    if (Array.isArray(payload.sorter) && payload.sorter.length === 0) {
      state.sorter = null
    } else {
      state.sorter = payload.sorter
    }
    state.paging = payload.paging
    state.items = payload.items
    state.search = payload.search
    state.native = payload.native
  },
  [GRID.MUTATIONS.SAVE_STATE]: (state) => {
    localStorage.setItem(createTableKey(state.namespace), JSON.stringify(prepareGridStateForSave(state)))
  },
  [GRID.MUTATIONS.GRID_UPDATE_ROW]: (state, { index, row }) => {
    if (state.items[index]) {
      state.items[index] = row

      state.items = [...state.items]
    }
  },
  [GRID.MUTATIONS.GRID_UPDATE_ROW_ITEM]: (state, { index, key, value }) => {
    if (state.items[index] && Object.prototype.hasOwnProperty.call(state.items[index], key)) {
      state.items[index][key] = value

      state.items = [...state.items]
    }
  },
  [GRID.MUTATIONS.SAVE_HEADERS]: (state, payload) => {
    state.headersMeta = payload.map((header) => ({ value: header.value, visible: header.visible }))

    localStorage.setItem(createHeaderKey(state.namespace), JSON.stringify(prepareGridHeaderStateForSave(state)))
  },
  [GRID.MUTATIONS.RESET]: (state) => {
    resetState(state, createState(state.namespace, { ...state.default }))
  },
}
