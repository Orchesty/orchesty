import { STORE } from './types'
import { AUTH } from './modules/auth/types'

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

export const callGraphQL = (dispatch, payload) => {
  return dispatchRoot(dispatch, STORE.ACTIONS.CALL_GRAPHQL, payload)
}

export const callApi = (dispatch, payload) => {
  return dispatchRoot(dispatch, STORE.ACTIONS.CALL_API, payload)
}

export const getLoggedUserId = (rootGetters) => {
  return rootGetters[withNamespace(AUTH.NAMESPACE, AUTH.GETTERS.GET_LOGGED_USER_ID)]
}

export const removeId = (data) => {
  const newData = { ...data }
  delete newData.id

  return newData
}
