import createState from "./state"
import { resetState } from "../../utils"
import { USER_TASKS } from "@/store/modules/userTasks/types"

export default {
  [USER_TASKS.MUTATIONS.USER_TASK_GET]: (state, data) => {
    state.userTask = data
  },
  [USER_TASKS.MUTATIONS.USER_TASK_FETCH_TASKS]: (state, data) => {
    state.userTasks = data
  },
  [USER_TASKS.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
