import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import type { Auth0VueClient } from '@auth0/auth0-vue'
import type { User } from '@/types/auth'
import * as authService from '@/services/authService'
import { useActivityTracker } from '@/composables/useActivityTracker'
import { isAuth0Enabled } from '@/auth/auth0-plugin'

const CHECK_INTERVAL_MS = 30_000
const REFRESH_IF_OLDER_THAN_MS = 2 * 60_000
const INACTIVITY_LIMIT_MS = 30 * 60_000

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const user = ref<User | null>(null)
  const lastTokenRefreshTime = ref(Date.now())
  const sessionTimerId = ref<number | null>(null)

  let _auth0: Auth0VueClient | null = null

  function setAuth0(auth0: Auth0VueClient) {
    _auth0 = auth0
  }

  const isAuthenticated = computed(() => {
    if (isAuth0Enabled) {
      return !!token.value
    }
    return !!token.value && !!user.value
  })

  const { lastActivityTime, touch: touchActivity } = useActivityTracker()

  function startSessionTimer(): void {
    if (isAuth0Enabled) return

    stopSessionTimer()

    sessionTimerId.value = window.setInterval(async () => {
      if (!isAuthenticated.value) return

      const now = Date.now()
      const inactiveFor = now - lastActivityTime.value

      if (inactiveFor > INACTIVITY_LIMIT_MS) {
        await logout()
        window.location.href = '/sign-in'
        return
      }

      const tokenAge = now - lastTokenRefreshTime.value
      if (tokenAge > REFRESH_IF_OLDER_THAN_MS) {
        await refreshAuthToken()
      }
    }, CHECK_INTERVAL_MS)
  }

  function stopSessionTimer(): void {
    if (sessionTimerId.value !== null) {
      clearInterval(sessionTimerId.value)
      sessionTimerId.value = null
    }
  }

  async function refreshAuthToken(): Promise<void> {
    if (isAuth0Enabled && _auth0) {
      try {
        const accessToken = await _auth0.getAccessTokenSilently()
        token.value = accessToken
        localStorage.setItem('auth_token', `Bearer ${accessToken}`)
      } catch (error) {
        console.error('Auth0 token refresh failed:', error)
      }
      return
    }

    try {
      const response = await authService.refreshToken()

      token.value = response.token
      localStorage.setItem('auth_token', response.token)

      const now = Date.now()
      lastTokenRefreshTime.value = now
      localStorage.setItem('lastTokenRefreshTime', String(now))

      if (response.email && response.id) {
        user.value = {
          id: response.id,
          email: response.email,
          settings: response.settings,
        }
        localStorage.setItem('auth_user', JSON.stringify(user.value))
      }
    } catch (error) {
      console.error('Token refresh failed:', error)
    }
  }

  async function handleAuth0Callback(auth0: Auth0VueClient): Promise<void> {
    _auth0 = auth0
    const accessToken = await auth0.getAccessTokenSilently()
    token.value = accessToken
    localStorage.setItem('auth_token', `Bearer ${accessToken}`)

    if (auth0.user.value) {
      user.value = {
        id: auth0.user.value.sub || '',
        email: auth0.user.value.email || '',
        settings: {},
      }
      localStorage.setItem('auth_user', JSON.stringify(user.value))
    }
  }

  async function login(email: string, password: string): Promise<void> {
    const response = await authService.login(email, password)

    token.value = response.token
    user.value = {
      id: response.id,
      email: response.email,
      settings: response.settings,
    }

    localStorage.setItem('auth_token', response.token)
    localStorage.setItem('auth_user', JSON.stringify(user.value))

    const now = Date.now()
    lastTokenRefreshTime.value = now
    localStorage.setItem('lastTokenRefreshTime', String(now))
    touchActivity()
    startSessionTimer()
  }

  async function logout(): Promise<void> {
    stopSessionTimer()

    if (isAuth0Enabled && _auth0) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      token.value = null
      user.value = null
      _auth0.logout({ logoutParams: { returnTo: window.location.origin } })
      return
    }

    await authService.logout()

    token.value = null
    user.value = null
  }

  function initializeAuth(): void {
    if (isAuth0Enabled) {
      return
    }

    const storedToken = localStorage.getItem('auth_token')
    const storedUser = localStorage.getItem('auth_user')

    if (storedToken && storedUser) {
      try {
        token.value = storedToken
        user.value = JSON.parse(storedUser)

        const now = Date.now()
        lastTokenRefreshTime.value = now
        localStorage.setItem('lastTokenRefreshTime', String(now))
        touchActivity()
        startSessionTimer()
      } catch (error) {
        console.error('Failed to parse stored user data:', error)
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_user')
      }
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    lastTokenRefreshTime,
    login,
    setAuth0,
    handleAuth0Callback,
    logout,
    initializeAuth,
    refreshAuthToken,
  }
})
