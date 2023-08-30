import { TOPOLOGIES } from './types'
import createState from './state'
import { resetState } from '../../utils'
import { createTree } from './utils'

export default {
  [TOPOLOGIES.MUTATIONS.DATA.MUTATE_TOPOLOGIES]: (state, data) => {
    state.topologies = createTree(data.topologies, data.folders)
    state.folders = data.folders
  },
  [TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_TOPOLOGY]: (state, data) => {
    state.topology = data
    localStorage.setItem('topology', JSON.stringify(data))
  },
  [TOPOLOGIES.MUTATIONS.DATA.MUTATE_SDK_NODES]: (state, data) => {
    localStorage.setItem('pipes-nodes-list', JSON.stringify(data))
  },
  [TOPOLOGIES.MUTATIONS.DATA.MUTATE_STATISTICS]: (state, data) => {
    state.statistics = data
  },
  [TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_NODES]: (state, data) => {
    state.nodeNames = data.items
  },
  [TOPOLOGIES.MUTATIONS.DATA.MUTATE_DASHBOARD]: (state, data) => {
    state.dashboard = data
  },
  [TOPOLOGIES.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
