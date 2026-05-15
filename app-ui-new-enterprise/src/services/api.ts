import axios, { type InternalAxiosRequestConfig } from 'axios'
import type { LoginResponse } from '@/types/auth'
import { isAuth0Active, getAuth0Plugin } from '@/auth/auth0-plugin'
import { useCloudMode } from '@/composables/useCloudMode'
import { BACKEND_URL, STORAGE_KEYS } from '@/config'

const api = axios.create({
  baseURL: BACKEND_URL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
})

// --- Token refresh queue ---
let isRefreshing = false
let refreshPromise: Promise<string> | null = null
let failedQueue: Array<{
  resolve: (token: string) => void
  reject: (error: unknown) => void
}> = []

const processQueue = (error: unknown, token: string | null = null) => {
  failedQueue.forEach(({ resolve, reject }) => {
    if (error) {
      reject(error)
    } else {
      resolve(token!)
    }
  })
  failedQueue = []
}

const SKIP_REFRESH_URLS = ['/api/user/check_logged', '/api/user/login', '/api/user/whoami']

let forceLogoutInProgress = false

function forceLogout() {
  if (forceLogoutInProgress) return
  forceLogoutInProgress = true

  localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN)
  localStorage.removeItem(STORAGE_KEYS.AUTH_USER)
  localStorage.removeItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION)

  const { cloudMode, cloudUrl } = useCloudMode()

  // Cloud mode takes precedence over Auth0 ENV state. Even if Auth0 ENVs
  // happen to be present, the cloud invariant says: send the user back to
  // the cloud frontend to retry the handoff. Setting CLOUD_HANDOFF_FAILED
  // surfaces a retry banner on the cloud sign-in (it does NOT trap the
  // router on the local /sign-in anymore — see router/index.ts).
  if (cloudMode.value && cloudUrl.value) {
    sessionStorage.setItem(STORAGE_KEYS.CLOUD_HANDOFF_FAILED, 'true')
    const returnUrl = encodeURIComponent(window.location.origin)
    window.location.href = `${cloudUrl.value}/sign-in?redirect_to=${returnUrl}&handoff_retry=1`
    return
  }

  if (isAuth0Active()) {
    Object.keys(localStorage)
      .filter((k) => k.startsWith('@@auth0spajs@@'))
      .forEach((k) => localStorage.removeItem(k))

    sessionStorage.setItem(STORAGE_KEYS.AUTH0_LOGIN_FAILED, 'true')
    if (window.location.pathname !== '/auth-error') {
      window.location.href = '/auth-error'
    }
    return
  }

  if (window.location.pathname !== '/sign-in') {
    window.location.href = '/sign-in'
  }
}

/**
 * Shared refresh call that coalesces concurrent callers
 * so only one network request is made at a time.
 */
async function doRefresh(): Promise<string> {
  if (refreshPromise) return refreshPromise

  refreshPromise = (async () => {
    const response = await api.get<LoginResponse>('/api/user/check_logged')
    const newToken = response.data.token

    localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, newToken)
    localStorage.setItem(STORAGE_KEYS.LAST_TOKEN_REFRESH, String(Date.now()))

    if (response.data.email && response.data.id) {
      localStorage.setItem(
        STORAGE_KEYS.AUTH_USER,
        JSON.stringify({
          id: response.data.id,
          email: response.data.email,
          settings: response.data.settings,
        }),
      )
    }

    return newToken
  })()

  try {
    return await refreshPromise
  } finally {
    refreshPromise = null
  }
}

/**
 * Attempt a single token refresh and retry the original request.
 * Returns the retried response or rejects with forceLogout.
 */
async function retryWithRefresh(
  originalRequest: InternalAxiosRequestConfig & { _retry?: boolean },
  refreshFn: () => Promise<string>,
): Promise<unknown> {
  if (originalRequest._retry) {
    forceLogout()
    return Promise.reject(new Error('Token refresh already attempted'))
  }
  originalRequest._retry = true
  try {
    const newToken = await refreshFn()
    // Any deployment that has Auth0 ENVs configured uses Bearer-style auth
    // (so do cloud-handoff sessions, since the cloud frontend also issues
    // JWTs). Legacy non-Auth0 self-hosted instances send the raw token.
    const useBearer = isAuth0Active() || localStorage.getItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION) === 'true'
    const prefix = useBearer ? 'Bearer ' : ''
    originalRequest.headers.Authorization = `${prefix}${newToken}`
    return api(originalRequest)
  } catch (refreshError) {
    forceLogout()
    return Promise.reject(refreshError)
  }
}

// Request interceptor — attach token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN)
    if (token) {
      // Bearer-style for Auth0 (standalone) AND cloud-handoff sessions. The
      // cloud frontend issues JWTs identical in shape to Auth0 access tokens
      // and the Symfony backend's `Auth0Authenticator` accepts both as long
      // as the audience/issuer match.
      const useBearer = isAuth0Active() || localStorage.getItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION) === 'true'
      config.headers.Authorization = useBearer ? `Bearer ${token}` : token
    }
    return config
  },
  (error) => Promise.reject(error),
)

// Response interceptor — 401 token refresh & retry
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean }

    if (error.response?.status !== 401 || !originalRequest) {
      return Promise.reject(error)
    }

    const hasCloudHandoff = localStorage.getItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION) === 'true'

    // Cloud-handoff session: refresh through our backend, NOT Auth0. The
    // SDK isn't installed in cloud mode, and even if it were, silent renewal
    // from the instance origin would hit the callback-mismatch wall.
    if (hasCloudHandoff) {
      if (SKIP_REFRESH_URLS.some((url) => originalRequest.url?.includes(url))) {
        return Promise.reject(error)
      }
      return retryWithRefresh(originalRequest, doRefresh)
    }

    // Standalone with Auth0 active: SDK silent renewal.
    if (isAuth0Active()) {
      if (SKIP_REFRESH_URLS.some((url) => originalRequest.url?.includes(url))) {
        return Promise.reject(error)
      }

      return retryWithRefresh(originalRequest, async () => {
        const plugin = getAuth0Plugin()
        if (!plugin) throw new Error('Auth0 plugin not available')
        const newToken = await plugin.getAccessTokenSilently()
        localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, newToken)
        return newToken
      })
    }

    // Legacy (non-Auth0) mode
    if (SKIP_REFRESH_URLS.some((url) => originalRequest.url?.includes(url))) {
      return Promise.reject(error)
    }

    if (originalRequest._retry) {
      forceLogout()
      return Promise.reject(error)
    }

    if (isRefreshing) {
      return new Promise<string>((resolve, reject) => {
        failedQueue.push({ resolve, reject })
      }).then((token) => {
        originalRequest.headers.Authorization = token
        return api(originalRequest)
      })
    }

    originalRequest._retry = true
    isRefreshing = true

    try {
      const newToken = await doRefresh()

      processQueue(null, newToken)

      originalRequest.headers.Authorization = newToken
      return api(originalRequest)
    } catch (refreshError) {
      processQueue(refreshError, null)
      forceLogout()
      return Promise.reject(refreshError)
    } finally {
      isRefreshing = false
    }
  },
)

export default api
