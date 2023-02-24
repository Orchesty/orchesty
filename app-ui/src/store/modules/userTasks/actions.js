import { USER_TASKS } from "./types"
import { callApi } from "../../utils"
import { API } from "../../../api"

import { addSuccessMessage } from "../../../services/utils/flashMessages"

export default {
  [USER_TASKS.ACTIONS.USER_TASK_ACCEPT]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.accept },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(
        dispatch,
        API.userTask.accept.id,
        "flashMessages.userTaskAccept"
      )

      return true
    } catch {
      return false
    }
  },
  [USER_TASKS.ACTIONS.USER_TASK_ACCEPT_LIST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.acceptAll },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(
        dispatch,
        API.userTask.acceptAll.id,
        "flashMessages.userTaskAcceptList"
      )

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
  [USER_TASKS.ACTIONS.USER_TASK_FETCH_TASKS]: async (
    { dispatch, commit },
    payload
  ) => {
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

      addSuccessMessage(
        dispatch,
        API.userTask.reject.id,
        "flashMessages.userTaskReject"
      )

      return true
    } catch {
      return false
    }
  },
  [USER_TASKS.ACTIONS.USER_TASK_REJECT_LIST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.rejectAll },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(
        dispatch,
        API.userTask.rejectAll.id,
        "flashMessages.userTaskRejectList"
      )

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

      await dispatch(USER_TASKS.ACTIONS.USER_TASK_GET, payload.id)

      addSuccessMessage(
        dispatch,
        API.userTask.update.id,
        "flashMessages.userTaskUpdate"
      )

      return true
    } catch {
      return false
    }
  },
}
