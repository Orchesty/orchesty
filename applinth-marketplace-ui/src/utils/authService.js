import { API } from '@/api'
import { callApi } from '@/utils/apiFetch'
import router from '../router/index.js'
import { ROUTES } from '../router/routes.js'

export class AuthService {
  /** @type {NodeJS.Timeout} */
  expireTimeout = null

  accessToken = ''

  /**
   * @typedef {Object} AuthenticationData
   * @property {string} access_token
   * @property {number} expires_in
   */

  /**
   * Authenticates app user.
   * @param {AuthenticationData} authenticationData
   */
  authenticate(authenticationData) {
    this.accessToken = authenticationData.access_token
    this.setExpireTimeout(authenticationData.expires_in)
  }

  /**
   * Invalidates authentication and resets store
   * @param redirect Whether redirect to login page
   */
  invalidateAuthentication(redirect = false) {
    if (this.expireTimeout) clearTimeout(this.expireTimeout)
    this.expireTimeout = null
    this.accessToken = ''
    if (redirect) {
      router.push({ name: ROUTES.Login })
    }
  }

  /**
   * Returns true / false whether current user is authenticated.
   * It includes request for an authentication extension attempt.
   * @param redirect Whether redirect to login page in case refresh attempt fails
   */
  isAuthenticatedOrRefresh(redirect = false) {
    if (this.expireTimeout) {
      return true
    }
    return this.tryRefreshAuthentication(redirect)
  }

  /**
   * @param {number} expiresIn - Time when token expires (Number of seconds since epoch)
   */
  setExpireTimeout(expiresIn) {
    if (this.expireTimeout) {
      clearTimeout(this.expireTimeout)
    }
    this.expireTimeout = setTimeout(() => {
      this.tryRefreshAuthentication(true)
    }, new Date(expiresIn * 1000) - new Date())
  }

  async tryRefreshAuthentication(redirect) {
    let authenticationData = null
    try {
      authenticationData = await callApi({
        requestData: API.auth.refreshAuth,
      })
    } catch {}

    if (authenticationData) {
      this.authenticate(authenticationData)
      return true
    } else {
      this.invalidateAuthentication(redirect)
      return false
    }
  }

  async initialAuthentication(initialToken) {
    let authenticationData = null
    try {
      authenticationData = await callApi({
        requestData: API.auth.initialAuth,
        params: { initialToken },
      })
    } catch (err) {
      console.error(err)
    }

    if (authenticationData) {
      this.authenticate(authenticationData)
      return true
    } else {
      this.invalidateAuthentication(true)
      return false
    }
  }
}

export const authService = new AuthService()
