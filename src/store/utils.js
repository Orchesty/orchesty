import { STORE } from "./types"

export const resetState = (state, initState) => {
  Object.keys(state).forEach((item) => {
    state[item] = initState[item]
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
