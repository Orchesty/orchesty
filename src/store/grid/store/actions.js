import { GRID } from './types'
import { callApi } from '../../utils'
import { DATA_GRIDS, GRID_REQUESTS } from '../grids'
import { createTableKey, prepareGridData } from '../utils'
import { createDefaultGridState } from './state'

export default {
  [GRID.ACTIONS.GRID_FETCH]: async ({ dispatch, commit, state, rootState }, payload) => {
    if (!payload.namespace) {
      throw new Error('Table must have namespace.')
    }

    if (!GRID_REQUESTS[payload.namespace]) {
      throw new Error('Table must have request.')
    }

    const gridData = prepareGridData(state, payload)

    const data = await callApi(dispatch, {
      requestData: { ...GRID_REQUESTS[payload.namespace] },
      params: gridData,
    })
    if (payload.namespace === DATA_GRIDS.OVERVIEW) {
      if (rootState.topologies.topology._id === payload.params.id) {
        commit(GRID.MUTATIONS.GRID_RESPONSE, { ...data, paramsId: payload.params.id })
      }
    } else {
      commit(GRID.MUTATIONS.GRID_RESPONSE, data)
    }
  },
  [GRID.ACTIONS.GRID_FILTER]: async ({ dispatch, state }, payload) => {
    await dispatch(GRID.ACTIONS.GRID_FETCH, {
      search: payload.search,
      namespace: payload.namespace,
      params: payload.params,
      filter: payload.filter,
      filterMeta: payload.filterMeta,
      native: payload.native,
      paging: {
        itemsPerPage: state.paging.itemsPerPage,
        page: 1,
      },
    })
  },
  [GRID.ACTIONS.GRID_FILTER_RESET]: async ({ dispatch, state }, payload) => {
    const defaultState = createDefaultGridState(payload.namespace, state.default)
    await dispatch(GRID.ACTIONS.GRID_FETCH, {
      search: defaultState.search,
      namespace: payload.namespace,
      filter: defaultState.filter,
      filterMeta: defaultState.filterMeta,
      params: payload.params,
      paging: {
        itemsPerPage: defaultState.paging.itemsPerPage,
        page: 1,
      },
    })

    localStorage.removeItem(createTableKey(state.namespace))
  },
  [GRID.ACTIONS.GRID_SEARCH]: async ({ dispatch }, payload) => {
    await dispatch(GRID.ACTIONS.GRID_FETCH, { namespace: payload.namespace, search: payload.search })
  },
  [GRID.ACTIONS.GRID_CHANGE_PAGING]: async ({ dispatch }, payload) => {
    await dispatch(GRID.ACTIONS.GRID_FETCH, { namespace: payload.namespace, ...payload })
  },
  [GRID.ACTIONS.GRID_UPDATE_ROW]: ({ commit }, payload) => {
    commit(GRID.MUTATIONS.GRID_UPDATE_ROW, { index: payload.index, row: payload.row })
  },
  [GRID.ACTIONS.GRID_UPDATE_ROW_ITEM]: ({ commit }, payload) => {
    commit(GRID.MUTATIONS.GRID_UPDATE_ROW_ITEM, { index: payload.index, key: payload.key, value: payload.value })
  },
  [GRID.ACTIONS.GRID_STATE_SAVE]: ({ commit }) => {
    commit(GRID.MUTATIONS.SAVE_STATE)
  },
  [GRID.ACTIONS.GRID_HEADERS_SAVE]: ({ commit }, payload) => {
    commit(GRID.MUTATIONS.SAVE_HEADERS, payload)
  },
}
