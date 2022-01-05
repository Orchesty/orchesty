import { NOTIFICATIONS } from './types'
import createState from './state'
import { resetState } from '../../utils'

export default {
  [NOTIFICATIONS.MUTATIONS.ADD]: (state, payload) => {
    let id = 0
    if (state.notifications.length > 2) {
      state.notifications.shift()
      id = state.notifications[state.notifications.length - 1].id + 1
    } else {
      id = state.notifications.length
    }

    state.notifications.push({ id, ...payload })
  },
  [NOTIFICATIONS.MUTATIONS.REMOVE]: (state, payload) => {
    state.notifications = state.notifications.filter((item) => item.id === payload)
  },
  [NOTIFICATIONS.MUTATIONS.GET_NOTIFICATION_LIST_RESPONSE]: (state, data) => {
    state.notifications = data
  },
  [NOTIFICATIONS.MUTATIONS.GET_NOTIFICATION_EVENTS_RESPONSE]: (state, data) => {
    state.events = data
  },
  [NOTIFICATIONS.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
