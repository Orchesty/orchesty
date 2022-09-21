import { TRASH } from './types'
import { callApi } from '../../utils'
import { API } from '../../../api'
import { addSuccessMessage } from '../../../services/utils/flashMessages'

export default {
  [TRASH.ACTIONS.TRASH_ACCEPT]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.accept },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(dispatch, API.userTask.accept.id, 'flashMessages.trash.accept')

      return true
    } catch {
      return false
    }
  },
  [TRASH.ACTIONS.TRASH_ACCEPT_LIST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.acceptAll },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(dispatch, API.userTask.acceptAll.id, 'flashMessages.trash.acceptList')

      return true
    } catch {
      return false
    }
  },
  [TRASH.ACTIONS.TRASH_TASK_GET]: async ({ dispatch, commit }, payload) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.userTask.getById },
        params: {
          id: payload,
        },
      })
      commit(TRASH.MUTATIONS.TRASH_GET, data)
      return true
    } catch {
      return false
    }
  },
  [TRASH.ACTIONS.TRASH_REJECT]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.reject },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(dispatch, API.userTask.reject.id, 'flashMessages.trash.reject')

      return true
    } catch {
      return false
    }
  },
  [TRASH.ACTIONS.TRASH_REJECT_LIST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.rejectAll },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(dispatch, API.userTask.rejectAll.id, 'flashMessages.trash.rejectList')

      return true
    } catch {
      return false
    }
  },
  [TRASH.ACTIONS.TRASH_UPDATE]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.update },
        params: {
          ...payload,
        },
      })

      dispatch(TRASH.ACTIONS.TRASH_TASK_GET, payload.id)

      addSuccessMessage(dispatch, API.userTask.update.id, 'flashMessages.trash.update')

      return true
    } catch {
      return false
    }
  },
}
