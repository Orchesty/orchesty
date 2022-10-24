import { TOPOLOGIES } from "@/store/modules/topologies/types"

export default {
  [TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY]: (state) => {
    return state.topologyActive
  },
  [TOPOLOGIES.GETTERS.GET_TOPOLOGIES_OVERVIEW]: (state) => {
    return state.topologiesOverview
  },
  [TOPOLOGIES.GETTERS.GET_ALL_TOPOLOGIES]: (state) => {
    return state.topologiesAll
  },
  [TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_NODES]: (state) => {
    return state.topologyActiveNodes
  },
  [TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_DIAGRAM]: (state) => {
    return state.topologyActiveDiagram
  },
  [TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_STATISTICS]: (state) => {
    return state.topologyActiveStatistics
  },
  [TOPOLOGIES.GETTERS.GET_DASHBOARD_PROCESSES]: (state) => {
    return state.processes
  },
  [TOPOLOGIES.GETTERS.GET_LAST_SELECTED_TOPOLOGY]: (state) => {
    return state.topologyLastSelected
  },
}
