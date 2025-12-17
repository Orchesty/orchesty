import createState from "./state"
import getters from "./getters"
import mutations from "./mutations"
import actions from "./actions"
export default {
  namespaced: true,
  state: createState(),
  getters,
  mutations,
  actions,
}
