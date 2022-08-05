import { GRID } from './types'
import { callApi } from '../../utils'
import { GRID_REQUESTS } from '@/services/utils/gridRequestEndpoints'
import { createDefaultGridState } from './state'

export default {
  [GRID.ACTIONS.GRID_FETCH]: async ({ dispatch, commit }, payload) => {
    if (!payload.namespace) {
      throw new Error('Table must have namespace.')
    }

    if (!GRID_REQUESTS[payload.namespace]) {
      throw new Error('Table must have request.')
    }

    const data = await callApi(dispatch, {
      requestData: { ...GRID_REQUESTS[payload.namespace] },
      params: payload,
    })

    commit(GRID.MUTATIONS.GRID_RESPONSE, data)
  },
  [GRID.ACTIONS.FETCH_WITH_DATA]: ({ dispatch, state }, payload) => {
    dispatch(GRID.ACTIONS.GRID_FETCH, {
      search: payload.search || state.search,
      namespace: payload.namespace || state.namespace,
      params: payload.params || state.params,
      filter: payload.filter || state.filter,
      paging: payload.paging || state.paging,
      sorter: payload.sorter || state.sorter,
    })
  },
  [GRID.ACTIONS.FETCH_WITH_INITIAL_STATE]: async ({ dispatch, state }, payload) => {
    const defaultState = createDefaultGridState(payload.namespace, state.backup)
    dispatch(GRID.ACTIONS.GRID_FETCH, {
      search: payload.search || defaultState.search,
      namespace: payload.namespace || defaultState.namespace,
      params: payload.params || defaultState.params,
      filter: payload.filter || defaultState.filter,
      paging: payload.paging || defaultState.paging,
      sorter: payload.sorter || defaultState.sorter,
    })
  },
  [GRID.ACTIONS.RESET]: ({ commit }) => {
    commit(GRID.MUTATIONS.RESET)
  },
}
