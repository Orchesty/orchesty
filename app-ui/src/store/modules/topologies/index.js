import createState from "./state"
import mutations from "./mutations"
import actions from "./actions"
import getters from "@/store/modules/topologies/getters"

export default {
  namespaced: true,
  state: createState(),
  getters,
  mutations,
  actions,
}
