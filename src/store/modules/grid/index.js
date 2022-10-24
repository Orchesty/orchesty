import createState from "./state"
import actions from "./actions"
import mutations from "./mutations"
import getters from "./getters"

export default (namespace, defaultState) => ({
  namespaced: true,
  state: createState(namespace, defaultState),
  actions,
  getters,
  mutations,
})
