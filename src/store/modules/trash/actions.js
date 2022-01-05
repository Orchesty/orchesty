import { TRASH } from './types'
import { callApi, dispatchRoot, withNamespace } from '../../utils'
import { API } from '../../../api'
import { GRID } from '../../grid/store/types'
import { DATA_GRIDS } from '../../grid/grids'
import { addSuccessMessage } from '../../../services/flashMessages'

export default {
  [TRASH.ACTIONS.TRASH_ACCEPT]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.userTask.accept },
        params: {
          ...payload,
        },
      })

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.TRASH, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.TRASH,
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

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.TRASH, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.TRASH,
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

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.TRASH, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.TRASH,
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

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.TRASH, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.TRASH,
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

      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.TRASH, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.TRASH,
      })

      dispatch(TRASH.ACTIONS.TRASH_TASK_GET, payload.id)

      addSuccessMessage(dispatch, API.userTask.update.id, 'flashMessages.trash.update')

      return true
    } catch {
      return false
    }
  },
}
