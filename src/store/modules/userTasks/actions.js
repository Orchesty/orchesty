import { USER_TASKS } from './types'
import { callApi, dispatchRoot, withNamespace } from '../../utils'
import { API } from '../../../api'
import { GRID } from '../grid/types'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import { addSuccessMessage } from '../../../services/utils/flashMessages'

export default {
  [USER_TASKS.ACTIONS.USER_TASK_ACCEPT]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.accept },
        params: {
          ...payload,
        },
      })

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.USER_TASK, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.USER_TASK,
        params: { id: payload.topologyID },
      })

      addSuccessMessage(dispatch, API.userTask.accept.id, 'flashMessages.userTask.accept')

      return true
    } catch {
      return false
    }
  },
  [USER_TASKS.ACTIONS.USER_TASK_ACCEPT_LIST]: async ({ dispatch, rootState }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.acceptAll },
        params: {
          ...payload,
        },
      })

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.USER_TASK, GRID.ACTIONS.FETCH_WITH_INITIAL_STATE), {
        namespace: DATA_GRIDS.USER_TASK,
        params: { id: payload.topologyID },
        paging: rootState[DATA_GRIDS.USER_TASK].paging,
      })

      addSuccessMessage(dispatch, API.userTask.acceptAll.id, 'flashMessages.userTask.acceptList')

      return true
    } catch {
      return false
    }
  },
  [USER_TASKS.ACTIONS.USER_TASK_GET]: async ({ dispatch, commit }, payload) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.userTask.getById },
        params: {
          id: payload,
        },
      })
      commit(USER_TASKS.MUTATIONS.USER_TASK_GET, data)
      return true
    } catch {
      return false
    }
  },
  [USER_TASKS.ACTIONS.USER_TASK_FETCH_TASKS]: async ({ dispatch, commit }, payload) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.userTask.grid },
        params: {
          ...payload,
        },
      })
      commit(USER_TASKS.MUTATIONS.USER_TASK_FETCH_TASKS, data)
      return true
    } catch {
      return false
    }
  },
  [USER_TASKS.ACTIONS.USER_TASK_REJECT]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.reject },
        params: {
          ...payload,
        },
      })

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.USER_TASK, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.USER_TASK,
        params: { id: payload.topologyID },
      })

      addSuccessMessage(dispatch, API.userTask.reject.id, 'flashMessages.userTask.reject')

      return true
    } catch {
      return false
    }
  },
  [USER_TASKS.ACTIONS.USER_TASK_REJECT_LIST]: async ({ dispatch, rootState }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.rejectAll },
        params: {
          ...payload,
        },
      })

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.USER_TASK, GRID.ACTIONS.FETCH_WITH_INITIAL_STATE), {
        namespace: DATA_GRIDS.USER_TASK,
        params: { id: payload.topologyID },
        paging: rootState[DATA_GRIDS.USER_TASK].paging,
      })
      addSuccessMessage(dispatch, API.userTask.rejectAll.id, 'flashMessages.userTask.rejectList')

      return true
    } catch {
      return false
    }
  },
  [USER_TASKS.ACTIONS.USER_TASK_UPDATE]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.update },
        params: {
          ...payload,
        },
      })

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.USER_TASK, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.USER_TASK,
        params: { id: payload.topologyID },
      })

      await dispatch(USER_TASKS.ACTIONS.USER_TASK_GET, payload.id)

      addSuccessMessage(dispatch, API.userTask.update.id, 'flashMessages.userTask.update')

      return true
    } catch {
      return false
    }
  },
}
