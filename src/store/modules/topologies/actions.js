import { TOPOLOGIES } from './types'
import { callApi } from '../../utils'
import { API } from '../../../api'
import { addSuccessMessage } from '@/services/flashMessages'

export default {
  [TOPOLOGIES.ACTIONS.TOPOLOGY.CREATE]: async ({ dispatch }, payload) => {
    return callApi(dispatch, {
      requestData: { ...API.topology.create },
      params: {
        ...payload,
      },
      throwError: true,
    })
      .then((res) => {
        dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
        return res
      })
      .catch(() => false)
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.SAVE_DIAGRAM]: async ({ dispatch }, payload) => {
    return callApi(dispatch, {
      requestData: { ...API.topology.saveDiagram },
      params: {
        ...payload,
      },
      throwError: true,
    }).then((response) => {
      addSuccessMessage(dispatch, API.topology.enable.id, `Topology ${response.name} saved successfully`)
      dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
      return response
    })
  },
  //DASHBOARD
  [TOPOLOGIES.ACTIONS.DATA.GET_DASHBOARD]: async ({ dispatch, commit }) => {
    const response = await callApi(dispatch, {
      requestData: { ...API.topology.getDashboard },
    })
    commit(TOPOLOGIES.MUTATIONS.DATA.MUTATE_DASHBOARD, response)
  },
  //SDK-NODES
  [TOPOLOGIES.ACTIONS.DATA.GET_SDK_NODES]: async ({ dispatch, commit }) => {
    let values = await callApi(dispatch, {
      requestData: { ...API.topology.getNodes },
    })
    commit(TOPOLOGIES.MUTATIONS.DATA.MUTATE_SDK_NODES, values)
  },
  [TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS]: async ({ dispatch, commit }, payload) => {
    const data = await callApi(dispatch, {
      requestData: { ...API.statistic.getList },
      params: {
        payload,
      },
    })
    commit(TOPOLOGIES.MUTATIONS.DATA.MUTATE_STATISTICS, data)
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM]: async ({ dispatch }, payload) => {
    return await callApi(dispatch, {
      requestData: { ...API.topology.getDiagram },
      params: { topologyId: payload.topologyID },
    })
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.ENABLE]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.topology.enable },
        params: { topologyId: payload.topologyID },
      })
      dispatch(TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID, payload.topologyID)
      dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
      addSuccessMessage(dispatch, API.topology.enable.id, 'flashMessages.topologies.enabled')

      return true
    } catch (err) {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.DISABLE]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.topology.disable },
        params: { topologyId: payload.topologyID },
      })
      dispatch(TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID, payload.topologyID)
      dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
      addSuccessMessage(dispatch, API.topology.disable.id, 'flashMessages.topologies.disabled')

      return true
    } catch (err) {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.RUN]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.topology.run },
        params: { topologyId: payload.topologyID, startingPoints: payload.startingPoints, body: payload.body },
        throwError: true,
      })
      addSuccessMessage(dispatch, API.topology.run.id, 'flashMessages.topologies.run')
      return true
    } catch (err) {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.TEST]: async ({ dispatch }, payload) => {
    try {
      const testResults = await callApi(dispatch, {
        requestData: { ...API.topology.test },
        params: { topologyId: payload.topologyID },
      })
      dispatch(TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID, { id: payload.topologyID, test: testResults })
      addSuccessMessage(dispatch, API.topology.test.id, 'flashMessages.topologies.tested')

      return true
    } catch (e) {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.PUBLISH]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.topology.publish },
        params: { topologyId: payload.topologyID },
      })
      dispatch(TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID, payload.topologyID)
      dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
      addSuccessMessage(dispatch, API.topology.publish.id, 'flashMessages.topologies.published')

      return true
    } catch (e) {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.NODES]: async ({ dispatch, commit }, payload) => {
    const data = await callApi(dispatch, {
      requestData: { ...API.topology.getTopologyNodes },
      params: { id: payload.id },
    })
    commit(TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_NODES, data)
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.RETURN_NODES]: async ({ dispatch }, payload) => {
    return await callApi(dispatch, {
      requestData: { ...API.topology.getTopologyNodes },
      params: { id: payload.id },
    })
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID]: async ({ commit, dispatch }, payload) => {
    let data
    if (payload.id) {
      data = await callApi(dispatch, {
        requestData: { ...API.topology.getById },
        params: { id: payload.id },
      })
    } else {
      data = await callApi(dispatch, {
        requestData: { ...API.topology.getById },
        params: { id: payload },
      })
    }

    if (payload.test) {
      commit(TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_TOPOLOGY, { ...data, test: payload.test })
    } else {
      commit(TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_TOPOLOGY, { ...data, test: null })
    }
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.GET_ID]: async ({ dispatch }, payload) => {
    return await callApi(dispatch, {
      requestData: { ...API.topology.getById },
      params: { id: payload },
    })
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.CLONE]: async ({ dispatch }, payload) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.topology.clone },
        params: { id: payload },
      })
      dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
      addSuccessMessage(dispatch, API.topology.clone.id, 'flashMessages.topologies.cloned')
      return response
    } catch (e) {
      return false
    }
  },
  [TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]: async ({ commit, dispatch }) => {
    const values = await Promise.all([
      callApi(dispatch, {
        requestData: { ...API.topology.getList },
      }),
      callApi(dispatch, {
        requestData: { ...API.folder.getList },
      }),
    ])
    commit(TOPOLOGIES.MUTATIONS.DATA.MUTATE_TOPOLOGIES, { topologies: values[0].items, folders: values[1].items })
  },
  [TOPOLOGIES.ACTIONS.DATA.GET_PROGRESS]: async ({ commit, dispatch }, payload) => {
    const data = await callApi(dispatch, {
      requestData: { ...API.topology.getProgress },
      params: { id: payload.id },
    })
    commit(TOPOLOGIES.MUTATIONS.DATA.MUTATE_TOPOLOGIES, data)
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.MOVE]: ({ dispatch }, payload) => {
    return callApi(dispatch, {
      requestData: { ...API.topology.move },
      params: {
        ...payload,
      },
      throwError: true,
    })
      .then(() => {
        dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
        return true
      })
      .catch(() => false)
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.DELETE]: async ({ dispatch, state, commit }, payload) => {
    if (state.topology && state.topology._id === payload.topologyId) {
      commit(TOPOLOGIES.MUTATIONS.TOPOLOGY.MUTATE_TOPOLOGY, null)
    }

    return callApi(dispatch, {
      requestData: { ...API.topology.delete },
      params: {
        ...payload,
      },
      throwError: true,
    })
      .then(() => {
        dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
        return true
      })
      .catch(() => false)
  },
  [TOPOLOGIES.ACTIONS.TOPOLOGY.EDIT]: ({ dispatch }, payload) => {
    return callApi(dispatch, {
      requestData: { ...API.topology.edit },
      params: {
        ...payload,
      },
      throwError: true,
    })
      .then(() => {
        dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
        return true
      })
      .catch(() => false)
  },
  [TOPOLOGIES.ACTIONS.FOLDER.CREATE]: async ({ dispatch }, payload) => {
    return await callApi(dispatch, {
      requestData: { ...API.folder.create },
      params: {
        ...payload,
      },
    })
      .then(() => {
        dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
        return true
      })
      .catch(() => false)
  },
  [TOPOLOGIES.ACTIONS.FOLDER.DELETE]: async ({ dispatch }, payload) => {
    return await callApi(dispatch, {
      requestData: { ...API.folder.delete },
      params: {
        ...payload,
      },
      throwError: true,
    })
      .then(() => {
        dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
        return true
      })
      .catch(() => false)
  },
  [TOPOLOGIES.ACTIONS.FOLDER.EDIT]: ({ dispatch }, payload) => {
    return callApi(dispatch, {
      requestData: { ...API.folder.edit },
      params: {
        ...payload,
      },
      throwError: true,
    })
      .then(() => {
        dispatch(TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES)
        return true
      })
      .catch(() => false)
  },
}
