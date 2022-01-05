import createState from './state'
import mutations from './mutations'
import actions from './actions'

export default {
  namespaced: true,
  state: createState(),
  mutations,
  actions,
}
