export const TOPOLOGIES = {
  NAMESPACE: 'topologies',
  ACTIONS: {
    DATA: {
      GET_TOPOLOGIES: 'DATA_GET_TOPOLOGIES',
      GET_PROGRESS: 'DATA_GET_PROGRESS',
      GET_STATISTICS: 'DATA_GET_STATISTICS',
      GET_DASHBOARD: 'DATA_GET_DASHBOARD',
      GET_SDK_NODES: 'DATA_GET_SDK_NODES',
    },
    TOPOLOGY: {
      RETURN_NODES: 'TOPOLOGY_RETURN_NODES',
      RUN: 'TOPOLOGY_RUN',
      CLONE: 'TOPOLOGY_CLONE',
      CREATE: 'TOPOLOGY_CREATE',
      MOVE: 'TOPOLOGY_MOVE',
      DELETE: 'TOPOLOGY_DELETE',
      EDIT: 'TOPOLOGY_EDIT',
      GET_BY_ID: 'TOPOLOGY_GET_BY_ID',
      ENABLE: 'TOPOLOGY_ENABLE',
      DISABLE: 'TOPOLOGY_DISABLE',
      TEST: 'TOPOLOGY_TEST',
      PUBLISH: 'TOPOLOGY_PUBLISH',
      NODES: 'TOPOLOGY_NODES',
      GET_ID: 'TOPOLOGY_GET_ID',
      GET_DIAGRAM: 'TOPOLOGY_GET_DIAGRAM',
      SAVE_DIAGRAM: 'TOPOLOGY_SAVE_DIAGRAM',
    },
    FOLDER: {
      CREATE: 'CREATE_FOLDER',
      DELETE: 'DELETE_FOLDER',
      EDIT: 'EDIT_FOLDER',
    },
  },
  MUTATIONS: {
    DATA: {
      MUTATE_SDK_NODES: 'DATA_MUTATE_SDK_NODES',
      MUTATE_STATISTICS: 'DATA_MUTATE_STATISTICS',
      MUTATE_TOPOLOGIES: 'DATA_MUTATE_TOPOLOGIES',
      MUTATE_DASHBOARD: 'DATA_MUTATE_DASHBOARD',
    },
    TOPOLOGY: {
      MUTATE_NODES: 'TOPOLOGY_MUTATE_NODES',
      MUTATE_TOPOLOGY: 'TOPOLOGY_MUTATE_TOPOLOGY',
    },
    RESET: 'RESET',
  },
}
