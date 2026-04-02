import axios, { type InternalAxiosRequestConfig } from 'axios'
import type { LoginResponse } from '@/types/auth'
import { isAuth0Enabled } from '@/auth/auth0-plugin'
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

  if (isAuth0Enabled) {
    Object.keys(localStorage)
      .filter((k) => k.startsWith('@@auth0spajs@@'))
      .forEach((k) => localStorage.removeItem(k))

    sessionStorage.setItem(STORAGE_KEYS.AUTH0_LOGIN_FAILED, 'true')
    if (window.location.pathname !== '/auth-error') {
      window.location.href = '/auth-error'
    }
    return
  }

  const { cloudMode, cloudUrl } = useCloudMode()
  if (cloudMode.value && cloudUrl.value) {
    sessionStorage.setItem(STORAGE_KEYS.CLOUD_HANDOFF_FAILED, 'true')
    window.location.href = '/sign-in'
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
    const prefix = isAuth0Enabled ? 'Bearer ' : ''
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
      config.headers.Authorization = isAuth0Enabled
        ? `Bearer ${token}`
        : token
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

    if (isAuth0Enabled) {
      if (SKIP_REFRESH_URLS.some((url) => originalRequest.url?.includes(url))) {
        return Promise.reject(error)
      }

      const hasCloudHandoff = localStorage.getItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION) === 'true'

      if (hasCloudHandoff) {
        return retryWithRefresh(originalRequest, doRefresh)
      }

      return retryWithRefresh(originalRequest, async () => {
        const { auth0Plugin } = await import('@/auth/auth0-plugin')
        if (!auth0Plugin) throw new Error('Auth0 plugin not available')
        const newToken = await auth0Plugin.getAccessTokenSilently()
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
