import { NOTIFICATIONS } from './types'
import { callApi, dispatchRoot, withNamespace } from '../../utils'
import { API } from '../../../api'
import { DATA_GRIDS } from '../../grid/grids'
import { GRID } from '../../grid/store/types'
import { addSuccessMessage } from '../../../services/flashMessages'

export default {
  [NOTIFICATIONS.ACTIONS.ADD]: ({ commit }, payload) => {
    commit(NOTIFICATIONS.MUTATIONS.ADD, payload)
  },
  [NOTIFICATIONS.ACTIONS.REMOVE]: ({ commit }, payload) => {
    commit(NOTIFICATIONS.MUTATIONS.REMOVE, payload)
  },
  [NOTIFICATIONS.ACTIONS.GET_NOTIFICATION_LIST_REQUEST]: async ({ dispatch, commit }) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.notification.grid },
      })

      commit(NOTIFICATIONS.MUTATIONS.GET_NOTIFICATION_LIST_RESPONSE, data.items)
      return true
    } catch {
      return false
    }
  },
  [NOTIFICATIONS.ACTIONS.GET_NOTIFICATION_EVENTS_REQUEST]: async ({ dispatch, commit }) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.notification.events },
      })

      commit(NOTIFICATIONS.MUTATIONS.GET_NOTIFICATION_EVENTS_RESPONSE, data.items)
      return true
    } catch {
      return false
    }
  },
  [NOTIFICATIONS.ACTIONS.UPDATE_NOTIFICATIONS_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.notification.update },
        params: {
          ...payload,
        },
        throwError: true,
      })

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.EVENTS, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.EVENTS,
      })
      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.NOTIFICATIONS, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.NOTIFICATIONS,
      })
      addSuccessMessage(dispatch, API.notification.update.id, 'flashMessages.notifications.update')

      return true
    } catch {
      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.EVENTS, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.EVENTS,
      })
      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.NOTIFICATIONS, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.NOTIFICATIONS,
      })
      return false
    }
  },
}
