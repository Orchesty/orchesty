import { NOTIFICATIONS } from './types'
import { callApi } from '../../utils'
import { API } from '../../../api'
import { addSuccessMessage } from '../../../services/utils/flashMessages'

export default {
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
      })

      dispatch(NOTIFICATIONS.ACTIONS.GET_NOTIFICATION_LIST_REQUEST)
      dispatch(NOTIFICATIONS.ACTIONS.GET_NOTIFICATION_EVENTS_REQUEST)

      addSuccessMessage(dispatch, API.notification.update.id, 'flashMessages.notifications.update')

      return true
    } catch {
      return false
    }
  },
}
