import { TOPOLOGIES } from './types'
import { callApi } from '../../utils'
import { API } from '../../../api'
import { addSuccessMessage } from '@/services/utils/flashMessages'

export default {
  [TOPOLOGIES.ACTIONS.TOPOLOGY.CREATE]: async ({ dispatch }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.create },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(dispatch, API.topology.enable.id, `Topology ${response.name} created!`)

      return response
    } catch {
      return false
    }
  },

  [TOPOLOGIES.ACTIONS.TOPOLOGY.SAVE_DIAGRAM]: async ({ dispatch }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.saveDiagram },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(dispatch, API.topology.enable.id, `Topology ${response.name} saved!`)

      return response
    } catch {
      return false
    }
  },

  //DASHBOARD
  [TOPOLOGIES.ACTIONS.DATA.GET_DASHBOARD]: async ({ dispatch, commit }) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.getDashboard },
      })

      commit(TOPOLOGIES.MUTATIONS.DATA.MUTATE_DASHBOARD, response)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.DATA.GET_DASHBOARD_PROCESSES]: async ({ dispatch, commit }) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.dashboard.getProcesses },
      })
      commit(TOPOLOGIES.MUTATIONS.DATA.MUTATE_DASHBOARD_PROCESSES, data.items)

      return true
    } catch {
      return false
    }
  },

  //SDK-NODES
  [TOPOLOGIES.ACTIONS.DATA.GET_SDK_NODES]: async ({ dispatch, commit }) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.getNodes },
      })

      commit(TOPOLOGIES.MUTATIONS.DATA.MUTATE_SDK_NODES, response)

      return true
    } catch {
      return false
    }
  },

  [TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS]: async ({ dispatch, commit }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.statistic.getList },
        params: {
          payload,
        },
      })

      commit(TOPOLOGIES.MUTATIONS.DATA.MUTATE_STATISTICS, response)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM]: async ({ dispatch, commit }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.getDiagram },
        params: payload,
      })

      commit(TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_DIAGRAM, response)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.RETURN_DIAGRAM]: async ({ dispatch }, payload) => {
    try {
      return await callApi(dispatch, {
        requestData: { ...API.topology.getDiagram },
        params: payload,
      })
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.SET_LOCAL_DIAGRAM]: ({ commit }, payload) => {
    try {
      commit(TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_DIAGRAM, payload)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.ENABLE]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.topology.enable },
        params: payload,
      })

      addSuccessMessage(dispatch, API.topology.enable.id, 'flashMessages.topologies.enabled')

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.DISABLE]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.topology.disable },
        params: payload,
      })

      addSuccessMessage(dispatch, API.topology.disable.id, 'flashMessages.topologies.disabled')

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.RUN]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.topology.run },
        params: { topologyId: payload.topologyID, startingPoints: payload.startingPoints, body: payload.body },
      })

      addSuccessMessage(dispatch, API.topology.run.id, 'flashMessages.topologies.run')

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.TEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.topology.test },
        params: payload,
      })

      addSuccessMessage(dispatch, API.topology.test.id, 'flashMessages.topologies.tested')

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.PUBLISH]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.topology.publish },
        params: payload,
      })

      addSuccessMessage(dispatch, API.topology.publish.id, 'flashMessages.topologies.published')

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.NODES]: async ({ dispatch, commit }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.getTopologyNodes },
        params: payload,
      })

      commit(TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_NODES, response)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.RETURN_NODES]: async ({ dispatch }, payload) => {
    try {
      return await callApi(dispatch, {
        requestData: { ...API.topology.getTopologyNodes },
        params: payload,
      })
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID]: async ({ commit, dispatch }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.getById },
        params: payload,
      })

      commit(TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_TOPOLOGY, response)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.CLONE]: async ({ dispatch }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.clone },
        params: payload,
      })

      addSuccessMessage(dispatch, API.topology.clone.id, `Topology ${response.name} cloned!`)

      return response
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]: async ({ commit, dispatch }) => {
    try {
      const response = await Promise.all([
        callApi(dispatch, {
          requestData: { ...API.topology.getList },
        }),
        callApi(dispatch, {
          requestData: { ...API.folder.getList },
        }),
      ])

      commit(TOPOLOGIES.MUTATIONS.DATA.MUTATE_TOPOLOGIES, { topologies: response[0].items, folders: response[1].items })

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.DATA.GET_PROGRESS]: async ({ commit, dispatch }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.getProgress },
        params: { id: payload.id },
      })

      commit(TOPOLOGIES.MUTATIONS.DATA.MUTATE_TOPOLOGIES, response)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.MOVE]: async ({ dispatch }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.move },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(dispatch, API.topology.enable.id, `Topology ${response.name} moved!`)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.DELETE]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.topology.delete },
        params: payload.id,
      })

      addSuccessMessage(dispatch, API.topology.enable.id, `Topology ${payload.name} deleted!`)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.EDIT]: async ({ dispatch }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.edit },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(dispatch, API.topology.enable.id, `Topology ${response.name} edited!`)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.FOLDER.CREATE]: async ({ dispatch }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.folder.create },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(dispatch, API.topology.enable.id, `Folder ${response.name} created!`)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.FOLDER.DELETE]: async ({ dispatch }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.folder.delete },
        params: payload,
      })

      addSuccessMessage(dispatch, API.topology.enable.id, `Folder ${response.name} deleted!`)

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.FOLDER.EDIT]: async ({ dispatch }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.folder.edit },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(dispatch, API.topology.enable.id, `Folder renamed to ${response.name}!`)

      return true
    } catch {
      return false
    }
  },

  [TOPOLOGIES.ACTIONS.NODE.UPDATE]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.topology.updateNode },
        params: {
          ...payload,
        },
      })

      return true
    } catch {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.RESET]: ({ commit }) => {
    commit(TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_TOPOLOGY, {})
  },
}
