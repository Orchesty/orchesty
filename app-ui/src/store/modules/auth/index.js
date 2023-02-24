import createState from "./state"
import getters from "./getters"
import actions from "./actions"
import mutations from "./mutations"

export default {
  namespaced: true,
  state: createState(),
  getters,
  actions,
  mutations,
}
