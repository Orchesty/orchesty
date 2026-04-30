import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import type { Auth0VueClient } from '@auth0/auth0-vue'
import type { User } from '@/types/auth'
import * as authService from '@/services/authService'
import { useActivityTracker } from '@/composables/useActivityTracker'
import { isAuth0Enabled, auth0Plugin } from '@/auth/auth0-plugin'
import { useCloudMode } from '@/composables/useCloudMode'
import { STORAGE_KEYS } from '@/config'

const CHECK_INTERVAL_MS = 30_000
const REFRESH_IF_OLDER_THAN_MS = 2 * 60_000
const INACTIVITY_LIMIT_MS = 90 * 60_000

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const user = ref<User | null>(null)
  const lastTokenRefreshTime = ref(Date.now())
  const sessionTimerId = ref<number | null>(null)

  let _auth0: Auth0VueClient | null = null

  const isAuthenticated = computed(() => {
    if (isAuth0Enabled) {
      return !!token.value
    }
    return !!token.value && !!user.value
  })

  const { lastActivityTime, touch: touchActivity } = useActivityTracker()

  function startSessionTimer(): void {
    if (isAuth0Enabled && !hasCloudHandoffSession()) return

    stopSessionTimer()

    sessionTimerId.value = window.setInterval(async () => {
      if (!isAuthenticated.value) return

      const now = Date.now()
      const inactiveFor = now - lastActivityTime.value

      if (inactiveFor > INACTIVITY_LIMIT_MS) {
        await logout()
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

  function hasCloudHandoffSession(): boolean {
    return localStorage.getItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION) === 'true'
  }

  async function refreshAuthToken(): Promise<void> {
    if (isAuth0Enabled && _auth0 && !hasCloudHandoffSession()) {
      try {
        const accessToken = await _auth0.getAccessTokenSilently()
        token.value = accessToken
        localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, accessToken)
      } catch (error) {
        console.error('Auth0 token refresh failed:', error)
      }
      return
    }

    try {
      const response = await authService.refreshToken()

      token.value = response.token
      localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, response.token)

      const now = Date.now()
      lastTokenRefreshTime.value = now
      localStorage.setItem(STORAGE_KEYS.LAST_TOKEN_REFRESH, String(now))

      if (response.email && response.id) {
        user.value = {
          id: response.id,
          email: response.email,
          settings: response.settings,
        }
        localStorage.setItem(STORAGE_KEYS.AUTH_USER, JSON.stringify(user.value))
      }
    } catch (error) {
      console.error('Token refresh failed:', error)
    }
  }

  async function handleAuth0Callback(auth0: Auth0VueClient): Promise<void> {
    _auth0 = auth0
    const accessToken = await auth0.getAccessTokenSilently()
    token.value = accessToken
    localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, accessToken)

    if (auth0.user.value) {
      user.value = {
        id: auth0.user.value.sub || '',
        email: auth0.user.value.email || '',
        picture: auth0.user.value.picture || undefined,
        settings: {},
      }
      localStorage.setItem(STORAGE_KEYS.AUTH_USER, JSON.stringify(user.value))
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

    localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, response.token)
    localStorage.setItem(STORAGE_KEYS.AUTH_USER, JSON.stringify(user.value))

    const now = Date.now()
    lastTokenRefreshTime.value = now
    localStorage.setItem(STORAGE_KEYS.LAST_TOKEN_REFRESH, String(now))
    touchActivity()
    startSessionTimer()
  }

  function clearAuth0Cache(): void {
    Object.keys(localStorage)
      .filter((k) => k.startsWith('@@auth0spajs@@'))
      .forEach((k) => localStorage.removeItem(k))
  }

  async function logout(): Promise<void> {
    stopSessionTimer()

    const { cloudMode, cloudUrl } = useCloudMode()

    localStorage.removeItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION)
    localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN)
    localStorage.removeItem(STORAGE_KEYS.AUTH_USER)
    // Drop the persisted Trace conversation on logout so user A's chat does
    // not leak to user B on a shared browser.
    localStorage.removeItem(STORAGE_KEYS.TRACE_HISTORY)
    token.value = null
    user.value = null

    if (isAuth0Enabled) {
      clearAuth0Cache()
    }

    if (cloudMode.value && cloudUrl.value) {
      if (auth0Plugin) {
        await auth0Plugin.logout({ openUrl: false })
      }
      window.location.href = `${cloudUrl.value}/sign-out`
      return
    }

    if (isAuth0Enabled && _auth0) {
      await _auth0.logout({ logoutParams: { returnTo: window.location.origin } })
      return
    }

    await authService.logout()
    window.location.href = '/sign-in'
  }

  function initializeAuth(): void {
    const storedToken = localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN)
    const storedUser = localStorage.getItem(STORAGE_KEYS.AUTH_USER)

    if (isAuth0Enabled && !hasCloudHandoffSession()) {
      return
    }

    if (storedToken && storedUser) {
      try {
        token.value = storedToken
        user.value = JSON.parse(storedUser)

        const now = Date.now()
        lastTokenRefreshTime.value = now
        localStorage.setItem(STORAGE_KEYS.LAST_TOKEN_REFRESH, String(now))
        touchActivity()
        startSessionTimer()
      } catch (error) {
        console.error('Failed to parse stored user data:', error)
        localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN)
        localStorage.removeItem(STORAGE_KEYS.AUTH_USER)
        localStorage.removeItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION)
      }
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    lastTokenRefreshTime,
    login,
    handleAuth0Callback,
    logout,
    initializeAuth,
    refreshAuthToken,
  }
})
