import createState from './state'
import actions from './actions'
import mutations from './mutations'
import getters from './getters'

export default {
  namespaced: true,
  state: createState(),
  actions,
  mutations,
  getters,
}
