import { NOTIFICATIONS } from '@/store/modules/notifications/types'

export default {
  [NOTIFICATIONS.GETTERS.GET_NOTIFICATIONS]: (state) => {
    return state.notifications
  },
  [NOTIFICATIONS.GETTERS.GET_EVENTS]: (state) => {
    return state.events
  },
}
