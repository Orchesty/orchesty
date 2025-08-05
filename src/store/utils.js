import { STORE } from "./types"

export const resetState = (state, initState, excludeKeys = []) => {
  Object.keys(state).forEach((item) => {
    if (!excludeKeys.includes(item)) {
      state[item] = initState[item]
    }
  })
}

export const resetModules = (commit, state) => {
  Object.keys(state).forEach((module) => {
    commit(`${module}/RESET`)
  })
}

export const withNamespace = (namespace, module) => {
  return `${namespace}/${module}`
}

export const dispatchRoot = (dispatch, action, payload) => {
  return dispatch(action, payload, { root: true })
}

export const callApi = (dispatch, payload) => {
  return dispatchRoot(dispatch, STORE.ACTIONS.CALL_API, payload)
}

export const callCustomApi = (dispatch, payload) => {
  return dispatchRoot(dispatch, STORE.ACTIONS.CALL_CUSTOM_API, payload)
}
