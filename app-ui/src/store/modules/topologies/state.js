import { LOCAL_STORAGE } from '@/services/enums/localStorageEnums'

export default () => ({
  topologyActive: JSON.parse(localStorage.getItem(LOCAL_STORAGE.TOPOLOGY_ACTIVE)) || null,
  topologyActiveStatistics: null,
  topologyActiveNodes: [],
  topologyActiveNodeNames: [],
  topologyActiveDiagram: null,
  topologiesOverview: null,
  topologiesAll: [],
})
