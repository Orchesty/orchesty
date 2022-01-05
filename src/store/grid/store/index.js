import createState from './state'
import actions from './actions'
import mutations from './mutations'

export default (namespace, defaultState) => ({
  namespaced: true,
  state: createState(namespace, defaultState),
  actions,
  mutations,
})
