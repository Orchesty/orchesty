import actions from './actions'
import mutations from './mutations'
import createState from './state'

export default {
  namespaced: true,
  state: createState(),
  actions,
  mutations,
}
