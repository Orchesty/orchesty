import createState from "./state"
import getters from "./getters"
import mutations from "./mutations"

export default {
  namespaced: true,
  state: createState(),
  getters,
  mutations,
}
