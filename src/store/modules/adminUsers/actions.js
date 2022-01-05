import { ADMIN_USERS } from './types'
import { API } from '@/api'
import { callApi, dispatchRoot, withNamespace } from '../../utils'
import { DATA_GRIDS } from '../../grid/grids'
import { GRID } from '../../grid/store/types'
import { addSuccessMessage } from '@/services/flashMessages'

export default {
  [ADMIN_USERS.ACTIONS.CREATE_USER_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.admin.create },
        params: {
          email: payload.email,
        },
        throwError: true,
      })
      addSuccessMessage(dispatch, API.admin.create.id, 'flashMessages.emailSent')
      return true
    } catch {
      return false
    }
  },
  [ADMIN_USERS.ACTIONS.GET_USER_REQUEST]: async ({ dispatch, commit }, payload) => {
    try {
      commit(ADMIN_USERS.MUTATIONS.GET_USER_RESPONSE, null)

      const data = await callApi(dispatch, {
        requestData: { ...API.admin.getById },
        params: {
          id: payload.id,
        },
        throwError: true,
      })

      commit(ADMIN_USERS.MUTATIONS.GET_USER_RESPONSE, data)

      return true
    } catch {
      return false
    }
  },
  [ADMIN_USERS.ACTIONS.UPDATE_USER_REQUEST]: async ({ dispatch, commit }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.admin.update },
        params: {
          ...payload,
        },
        throwError: true,
      })
      // @todo missing group in response
      commit(ADMIN_USERS.MUTATIONS.UPDATE_USER_RESPONSE, payload.data)

      return true
    } catch {
      return false
    }
  },
  [ADMIN_USERS.ACTIONS.DELETE_USER_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.admin.delete },
        params: {
          id: payload.id,
        },
        throwError: true,
      })

      await dispatchRoot(dispatch, withNamespace(DATA_GRIDS.ADMIN_USERS_LIST, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.ADMIN_USERS_LIST,
      })
      addSuccessMessage(dispatch, API.admin.delete.id, 'flashMessages.user.delete')

      return true
    } catch {
      return false
    }
  },
}
