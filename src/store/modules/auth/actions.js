import { AUTH } from './types'
import { API } from '@/api'
import { callApi } from '../../utils'
import router from '../../../services/router'
import { ROUTES } from '@/services/enums/routerEnums'
import { addErrorMessage, addSuccessMessage } from '@/services/utils/flashMessages'
import { ERROR_TYPE } from '../api/types'
import { logout } from '@/services/utils/utils'

export default {
  [AUTH.ACTIONS.LOGIN_REQUEST]: async ({ commit, dispatch }, payload) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.auth.login },
        params: {
          email: payload.email,
          password: payload.password,
        },
        throwError: true,
      })

      commit(AUTH.MUTATIONS.LOGIN_RESPONSE, data)
      await router.push({ name: ROUTES.DASHBOARD })
      addSuccessMessage(dispatch, API.auth.forgotPassword.id, 'Welcome back!')
    } catch (e) {
      addErrorMessage(dispatch, API.auth.forgotPassword.id, e)
    }
  },
  [AUTH.ACTIONS.CHECK_LOGGED_REQUEST]: async ({ dispatch, commit }) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.auth.checkLogged },
        throwError: true,
      })

      commit(AUTH.MUTATIONS.CHECK_LOGGED_RESPONSE, data)
      return true
    } catch {
      await logout(commit, dispatch)

      return false
    }
  },
  [AUTH.ACTIONS.LOGOUT_REQUEST]: async ({ commit, dispatch }) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.auth.logout },
        throwError: true,
      })
    } finally {
      await logout(commit, dispatch)
    }
  },
  [AUTH.ACTIONS.FORGOT_PASSWORD_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.auth.forgotPassword },
        params: {
          email: payload.email,
        },
      })

      addSuccessMessage(dispatch, API.auth.forgotPassword.id, 'flashMessages.forgotPassword')

      return payload.email
    } catch {
      return false
    }
  },
  [AUTH.ACTIONS.CHECK_TOKEN_REQUEST]: async ({ dispatch }, payload) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.auth.checkToken, errorType: ERROR_TYPE.NONE },
        params: {
          token: payload.token,
        },
        throwError: true,
      })

      return data.email
    } catch {
      return null
    }
  },
  [AUTH.ACTIONS.SET_PASSWORD_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.auth.setPassword },
        params: {
          token: payload.token,
          password: payload.password,
        },
        throwError: true,
      })

      await router.push({ name: ROUTES.PASSWORD_CHANGED })

      return true
    } catch {
      return false
    }
  },
  [AUTH.ACTIONS.CHECK_REGISTER_TOKEN_REQUEST]: async ({ dispatch }, payload) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.auth.checkRegisterToken, errorType: ERROR_TYPE.NONE },
        params: {
          token: payload.token,
        },
        throwError: true,
      })

      return data.email
    } catch {
      return null
    }
  },
  // PROFILE
  [AUTH.ACTIONS.CHANGE_PASSWORD_REQUEST]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.auth.changePassword },
        params: {
          password: payload.password,
          old_password: payload.old_password,
        },
        throwError: true,
      })

      addSuccessMessage(dispatch, API.auth.changePassword.id, 'flashMessages.changePassword')

      return true
    } catch {
      return false
    }
  },
  [AUTH.ACTIONS.UPDATE_CONTACT_REQUEST]: async ({ dispatch, commit }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.contact.update },
        params: {
          ...payload,
        },
        throwError: true,
      })

      commit(AUTH.MUTATIONS.UPDATE_CONTACT_RESPONSE, payload)

      return true
    } catch {
      return false
    }
  },
}
