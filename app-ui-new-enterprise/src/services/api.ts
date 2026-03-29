import axios, { type InternalAxiosRequestConfig } from 'axios'
import type { LoginResponse } from '@/types/auth'
import { isAuth0Enabled } from '@/auth/auth0-plugin'

const api = axios.create({
  baseURL: import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.66:8085',
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

const SKIP_REFRESH_URLS = ['/api/user/check_logged', '/api/user/login']

function forceLogout() {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')

  if (isAuth0Enabled) {
    Object.keys(localStorage)
      .filter((k) => k.startsWith('@@auth0spajs@@'))
      .forEach((k) => localStorage.removeItem(k))
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

    localStorage.setItem('auth_token', newToken)
    localStorage.setItem('lastTokenRefreshTime', String(Date.now()))

    if (response.data.email && response.data.id) {
      localStorage.setItem(
        'auth_user',
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

// Request interceptor — attach token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = isAuth0Enabled && !token.startsWith('Bearer ')
        ? `Bearer ${token}`
        : token
    }
    return config
  },
  (error) => Promise.reject(error),
)

// Response interceptor — fallback 401 retry
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean }

    if (error.response?.status !== 401 || !originalRequest) {
      return Promise.reject(error)
    }

    // In Auth0 mode, try silent token refresh via auth0 plugin
    if (isAuth0Enabled) {
      if (originalRequest._retry) {
        forceLogout()
        return Promise.reject(error)
      }
      originalRequest._retry = true
      try {
        const { auth0Plugin } = await import('@/auth/auth0-plugin')
        if (auth0Plugin) {
          const newToken = await auth0Plugin.getAccessTokenSilently()
          localStorage.setItem('auth_token', `Bearer ${newToken}`)
          originalRequest.headers.Authorization = `Bearer ${newToken}`
          return api(originalRequest)
        }
        forceLogout()
        return Promise.reject(error)
      } catch (refreshError) {
        forceLogout()
        return Promise.reject(refreshError)
      }
    }

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
