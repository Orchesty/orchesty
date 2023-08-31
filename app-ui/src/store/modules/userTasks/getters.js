import { USER_TASKS } from '@/store/modules/userTasks/types'

export default {
  [USER_TASKS.GETTERS.GET_USER_TASKS]: (state) => {
    return state.userTasks
  },
}
