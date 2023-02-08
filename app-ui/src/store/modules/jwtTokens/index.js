import actions from "@/store/modules/jwtTokens/actions"
import createState from "@/store/modules/jwtTokens/state"
import mutations from "@/store/modules/jwtTokens/mutations"

export default {
  namespaced: true,
  actions,
  state: createState(),
  mutations,
}
