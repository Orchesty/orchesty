import { APP_STORE } from './types'
import { callApi, dispatchRoot, withNamespace } from '../../utils'
import { API } from '../../../api'
import { addSuccessMessage } from '@/services/flashMessages'
import { DATA_GRIDS } from '@/store/grid/grids'
import { GRID } from '@/store/grid/store/types'

export default {
  [APP_STORE.ACTIONS.GET_INSTALLED_APP]: async ({ commit, dispatch }, payload) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.appStore.getInstalledApp },
        params: { key: payload.key, userId: payload.userId },
      })

      commit(APP_STORE.MUTATIONS.GET_APP_RESPONSE, data)
      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.GET_AVAILABLE_APPS]: async ({ dispatch, commit }) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.appStore.getAvailableApps },
        params: { filter: [], paging: 1, sorter: null, search: '' },
      })
      commit(APP_STORE.MUTATIONS.GET_AVAILABLE_APPS, data)
      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.GET_INSTALLED_APPS]: async ({ dispatch, commit }, payload) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.appStore.getInstalledApps },
        params: { params: { id: payload.userId } },
      })
      commit(APP_STORE.MUTATIONS.GET_INSTALLED_APPS, data)
      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.appStore.uninstallApp },
        params: { key: payload.key, userId: payload.userId },
      })
      dispatchRoot(dispatch, withNamespace(DATA_GRIDS.INSTALLED_APPS, GRID.ACTIONS.GRID_FETCH), {
        namespace: DATA_GRIDS.INSTALLED_APPS,
        params: { id: payload.userId },
      })

      addSuccessMessage(dispatch, API.admin.delete.id, 'flashMessages.appStore.appUninstalled')

      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.INSTALL_APP_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.appStore.installApp },
        params: { key: payload.key, userId: payload.userId },
      })
      addSuccessMessage(dispatch, API.admin.delete.id, 'flashMessages.appStore.appInstalled')

      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.SAVE_APP_SETTINGS]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.appStore.saveSettings },
        params: { key: payload.key, userId: payload.userId, data: payload.data },
      })
      addSuccessMessage(dispatch, API.admin.delete.id, 'flashMessages.appStore.appSaved')

      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.GET_AVAILABLE_APP]: async ({ dispatch, commit }, payload) => {
    try {
      commit(APP_STORE.MUTATIONS.GET_APP_RESPONSE, null)
      const data = await callApi(dispatch, {
        requestData: { ...API.appStore.getAvailableApp },
        params: { key: payload.key },
      })
      commit(APP_STORE.MUTATIONS.GET_APP_RESPONSE, data)
      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.SUBSCRIBE_WEBHOOK]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.appStore.subscribeToWebhook },
        params: { key: payload.key, userId: payload.userId, data: payload.data },
      })
      addSuccessMessage(dispatch, API.admin.delete.id, 'flashMessages.appStore.subscribed')

      return true
    } catch (e) {
      return false
    }
  },
}
