import actions from "./actions"
import mutations from "./mutations"
import createState from "./state"
import getters from "@/store/modules/userTasks/getters"

export default {
  namespaced: true,
  getters,
  state: createState(),
  actions,
  mutations,
}
