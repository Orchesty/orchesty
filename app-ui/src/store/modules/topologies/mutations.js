import { TOPOLOGIES } from './types'
import createState from './state'
import { resetState } from '../../utils'
import { createTree } from './utils'
import { LOCAL_STORAGE } from '@/services/enums/localStorageEnums'

export default {
  [TOPOLOGIES.MUTATIONS.DATA.MUTATE_TOPOLOGIES]: (state, data) => {
    state.topologiesAll = createTree(data.topologies, data.folders)
  },
  [TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_TOPOLOGY]: (state, data) => {
    state.topologyActive = data
    localStorage.setItem(LOCAL_STORAGE.TOPOLOGY_ACTIVE, JSON.stringify(data))
  },
  [TOPOLOGIES.MUTATIONS.DATA.MUTATE_SDK_NODES]: (state, data) => {
    localStorage.setItem(LOCAL_STORAGE.SDK_OPTIONS, JSON.stringify(data))
  },
  [TOPOLOGIES.MUTATIONS.DATA.MUTATE_STATISTICS]: (state, data) => {
    state.topologyActiveStatistics = data
  },
  [TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_NODES]: (state, data) => {
    state.topologyActiveNodes = data.items
  },
  [TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_DIAGRAM]: (state, data) => {
    state.topologyActiveDiagram = data
  },
  [TOPOLOGIES.MUTATIONS.DATA.MUTATE_DASHBOARD]: (state, data) => {
    state.topologiesOverview = data
  },
  [TOPOLOGIES.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
