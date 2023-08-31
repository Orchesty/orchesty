import { NOTIFICATIONS } from './types'
import createState from './state'
import { resetState } from '../../utils'

export default {
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
