import { IMPLEMENTATIONS } from './types'
import { callApi, dispatchRoot, withNamespace } from '../../utils'
import { API } from '../../../api'
import { GRID } from '../../grid/store/types'
import { DATA_GRIDS } from '../../grid/grids'
import { addSuccessMessage } from '../../../services/flashMessages'

export default {
  [IMPLEMENTATIONS.ACTIONS.SET_FILE_IMPLEMENTATIONS]: ({ commit }, payload) => {
    commit(IMPLEMENTATIONS.MUTATIONS.SET_FILE_IMPLEMENTATIONS, payload)
  },
  [IMPLEMENTATIONS.ACTIONS.CREATE_IMPLEMENTATIONS_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.implementation.create },
        params: {
          ...payload,
        },
        throwError: true,
      })

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.IMPLEMENTATIONS_LIST, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.IMPLEMENTATIONS_LIST,
      })

      addSuccessMessage(dispatch, API.implementation.update.id, 'flashMessages.implementations.create')

      return true
    } catch {
      return false
    }
  },
  [IMPLEMENTATIONS.ACTIONS.UPDATE_IMPLEMENTATIONS_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.implementation.update },
        params: {
          ...payload,
        },
      })

      await dispatchRoot(dispatch, withNamespace(DATA_GRIDS.IMPLEMENTATIONS_LIST, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.IMPLEMENTATIONS_LIST,
      })

      addSuccessMessage(dispatch, API.implementation.update.id, 'flashMessages.implementations.update')

      return true
    } catch {
      return false
    }
  },
  [IMPLEMENTATIONS.ACTIONS.GET_IMPLEMENTATION_REQUEST]: async ({ dispatch, commit }, payload) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.implementation.getById },
        params: {
          ...payload,
        },
      })

      commit(IMPLEMENTATIONS.MUTATIONS.GET_IMPLEMENTATION_RESPONSE, data)
      return true
    } catch {
      return false
    }
  },
  [IMPLEMENTATIONS.ACTIONS.LIST_IMPLEMENTATIONS]: async ({ dispatch, commit }) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.implementation.getList },
      })

      commit(IMPLEMENTATIONS.MUTATIONS.LIST_IMPLEMENTATIONS, data)
      return true
    } catch {
      return false
    }
  },
  [IMPLEMENTATIONS.ACTIONS.DELETE_IMPLEMENTATIONS_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.implementation.delete },
        params: {
          id: payload.id,
        },
      })

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.IMPLEMENTATIONS_LIST, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.IMPLEMENTATIONS_LIST,
      })

      addSuccessMessage(dispatch, API.implementation.delete.id, 'flashMessages.implementations.delete')
      return true
    } catch {
      return false
    }
  },
}
