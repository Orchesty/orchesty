export default {
  create: {
    id: 'TOPOLOGY_CREATE',
    request: ({ name, description, folder }) => ({
      url: '/topologies',
      method: 'POST',
      data: {
        name,
        descr: description,
        category: folder || null,
      },
    }),
  },
  getById: {
    id: 'TOPOLOGY_GET_BY_ID',
    request: (id) => ({
      url: `/topologies/${id}`,
      method: 'GET',
    }),
  },
  getList: {
    id: 'TOPOLOGIES_GET_LIST',
    request: () => ({
      url: '/topologies',
      method: 'GET',
    }),
  },
  getTopologyNodes: {
    id: 'TOPOLOGY_GET_NODES',
    request: (id) => ({
      url: `/topologies/${id}/nodes`,
      method: 'GET',
    }),
  },
  updateNode: {
    id: 'UPDATE_NODE',
    request: ({ nodeId, enabled }) => ({
      url: `/nodes/${nodeId}`,
      method: 'PATCH',
      data: {
        enabled,
      },
    }),
  },
  getNodes: {
    id: 'TOPOLOGY_NODE_LIST',
    request: () => ({
      url: '/nodes/list/name',
      method: 'GET',
    }),
  },
  clone: {
    id: 'TOPOLOGY_CLONE',
    request: (id) => ({
      url: `/topologies/${id}/clone`,
      method: 'POST',
    }),
  },
  move: {
    id: 'TOPOLOGY_MOVE',
    request: ({ topologyId, categoryId }) => ({
      url: `/topologies/${topologyId}`,
      method: 'PATCH',
      data: {
        category: categoryId,
      },
    }),
  },
  delete: {
    id: 'TOPOLOGY_DELETE',
    request: (id) => ({
      url: `/topologies/${id}`,
      method: 'DELETE',
    }),
  },
  edit: {
    id: 'TOPOLOGY_EDIT',
    request: ({ id, data }) => ({
      url: `/topologies/${id}`,
      method: 'PUT',
      data: {
        ...data,
      },
    }),
  },
  getDiagram: {
    id: 'TOPOLOGY_GET_DIAGRAM',
    request: (id) => ({
      url: `/topologies/${id}/schema.bpmn`,
      method: 'GET',
    }),
  },
  saveDiagram: {
    id: 'TOPOLOGY_SAVE_DIAGRAM',
    request: (data) => ({
      url: `/topologies/${data.id}/schema.bpmn`,
      method: 'PUT',
      data: data.xml,
    }),
  },
  getProgress: {
    id: 'TOPOLOGY_GET_PROGRESS',
    request: ({ id }) => ({
      url: `/progress/topology/${id}`,
      method: 'GET',
    }),
  },
  publish: {
    id: 'TOPOLOGY_PUBLISH',
    request: (id) => ({
      url: `/topologies/${id}/publish`,
      method: 'POST',
    }),
  },
  run: {
    id: 'TOPOLOGY_RUN',
    request: ({ topologyId, startingPoints, body }) => ({
      url: `/topologies/${topologyId}/run`,
      method: 'POST',
      data: { startingPoints, body },
    }),
  },
  enable: {
    id: 'TOPOLOGY_ENABLE',
    request: (id) => ({
      url: `/topologies/${id}`,
      method: 'PATCH',
      data: {
        enabled: true,
      },
    }),
  },
  disable: {
    id: 'TOPOLOGY_DISABLE',
    request: (id) => ({
      url: `/topologies/${id}`,
      method: 'PATCH',
      data: {
        enabled: false,
      },
    }),
  },
  test: {
    id: 'TOPOLOGY_TEST',
    request: (id) => ({
      url: `/topologies/${id}/test`,
      method: 'GET',
    }),
  },
  getDashboard: {
    id: 'TOPOLOGY_GET_DASHBOARD',
    request: () => ({
      url: '/dashboards/default',
      method: 'GET',
    }),
  },
  getLogs: {
    id: 'GET_LOGS',
    request: (data) => ({
      url: `/logs?filter=${JSON.stringify(data)}`,
      method: 'GET',
    }),
  },
  getLogsByID: {
    id: 'TOPOLOGY_GET_LOGS_BY_ID',
    request: (data) => {
      return {
        url: `/logs?filter=${JSON.stringify(data)}`,
        method: 'GET',
      }
    },
  },
  getNodeLogsByID: {
    id: 'NODE_GET_LOGS_BY_ID',
    request: (data) => {
      if (data.params) {
        data.filter.forEach((conditionsArray) => {
          conditionsArray.forEach((condition) => {
            if (condition.column === 'topology_id') {
              condition.value = [data.params.topologyID]
            }
            if (condition.column === 'node_id') {
              condition.value = [data.params.nodeID]
            }
          })
        })
      }
      return {
        url: `/logs?filter=${JSON.stringify(data)}`,
        method: 'GET',
      }
    },
  },
}
