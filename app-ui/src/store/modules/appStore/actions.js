import { APP_STORE } from "./types"
import { callApi, dispatchRoot, withNamespace } from "../../utils"
import { API } from "../../../api"
import { addSuccessMessage } from "@/services/utils/flashMessages"
import { DATA_GRIDS } from "@/services/enums/dataGridEnums"
import { GRID } from "@/store/modules/grid/types"

export default {
  [APP_STORE.ACTIONS.GET_AVAILABLE_APPS]: async ({ dispatch, commit }) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.appStore.getAvailableApps },
      })

      commit(APP_STORE.MUTATIONS.GET_AVAILABLE_APPS, response)

      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.GET_INSTALLED_APPS]: async (
    { dispatch, commit },
    payload
  ) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.appStore.getInstalledApps },
        params: payload,
      })

      commit(APP_STORE.MUTATIONS.GET_INSTALLED_APPS, response)

      return true
    } catch (e) {
      return false
    }
  },

  [APP_STORE.ACTIONS.GET_INSTALLED_APP]: async (
    { commit, dispatch },
    payload
  ) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.appStore.getInstalledApp },
        params: payload,
      })

      commit(APP_STORE.MUTATIONS.GET_APP_RESPONSE, response)

      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.appStore.uninstallApp },
        params: payload,
      })

      dispatchRoot(
        dispatch,
        withNamespace(DATA_GRIDS.INSTALLED_APPS, GRID.ACTIONS.GRID_FETCH),
        {
          namespace: DATA_GRIDS.INSTALLED_APPS,
          params: { id: payload.userId },
        }
      )

      addSuccessMessage(
        dispatch,
        API.admin.delete.id,
        "flashMessages.appUninstalled"
      )

      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.INSTALL_APP_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.appStore.installApp },
        params: payload,
      })
      addSuccessMessage(
        dispatch,
        API.admin.delete.id,
        "flashMessages.appInstalled"
      )

      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.SAVE_APP_SETTINGS]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.appStore.saveSettings },
        params: payload,
      })
      addSuccessMessage(dispatch, API.admin.delete.id, "flashMessages.appSaved")

      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.GET_AVAILABLE_APP]: async (
    { dispatch, commit },
    payload
  ) => {
    try {
      const response = await callApi(dispatch, {
        requestData: { ...API.appStore.getAvailableApp },
        params: payload,
      })

      commit(APP_STORE.MUTATIONS.GET_APP_RESPONSE, response)

      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.APP_SET_PASSWORD]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.appStore.setPasswordApp },
        params: payload,
      })

      addSuccessMessage(
        dispatch,
        API.admin.delete.id,
        "flashMessages.passwordChanged"
      )

      return true
    } catch {
      return false
    }
  },
  [APP_STORE.ACTIONS.SUBSCRIBE_WEBHOOK]: async ({ dispatch }, payload) => {
    try {
      const res = await callApi(dispatch, {
        requestData: { ...API.appStore.subscribeToWebhook },
        params: payload,
      })

      const success = res.length > 0 || res[0]

      if (success) {
        addSuccessMessage(
          dispatch,
          API.appStore.subscribeToWebhook.id,
          "flashMessages.subscribed"
        )
      }

      return success
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.UNSUBSCRIBE_WEBHOOK]: async ({ dispatch }, payload) => {
    try {
      const res = await callApi(dispatch, {
        requestData: { ...API.appStore.unsubscribeToWebhook },
        params: payload,
      })

      const success = res.length > 0 || res[0]

      if (success) {
        addSuccessMessage(
          dispatch,
          API.appStore.unsubscribeToWebhook.id,
          "flashMessages.unsubscribed"
        )
      }

      return success
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.ACTIVATE]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.appStore.activateApp },
        params: payload,
      })
      addSuccessMessage(
        dispatch,
        API.appStore.activateApp.id,
        payload.data.enabled
          ? "flashMessages.activated"
          : "flashMessages.deactivated"
      )

      return true
    } catch (e) {
      return false
    }
  },
  [APP_STORE.ACTIONS.RESET]: async ({ commit }) => {
    commit(APP_STORE.MUTATIONS.RESET)
  },
}
