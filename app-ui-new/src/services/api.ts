import axios, { type InternalAxiosRequestConfig } from 'axios'
import type { LoginResponse } from '@/types/auth'

// Create axios instance with base configuration
const api = axios.create({
  baseURL: import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.66:8080',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true, // Enable cookies (for refreshToken)
})

// --- Token refresh queue ---
let isRefreshing = false
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

/** URLs that should never trigger a token refresh retry */
const SKIP_REFRESH_URLS = ['/api/user/check_logged', '/api/user/login']

/** Clear auth data and redirect to sign-in */
function forceLogout() {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  if (window.location.pathname !== '/sign-in') {
    window.location.href = '/sign-in'
  }
}

// Request interceptor - Add JWT token to all requests
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      // Note: Backend expects token directly without "Bearer " prefix
      config.headers.Authorization = token
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor - Handle 401 with token refresh and retry
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean }

    // Only handle 401 responses
    if (error.response?.status !== 401 || !originalRequest) {
      return Promise.reject(error)
    }

    // Don't retry for auth endpoints (prevent infinite loop)
    if (SKIP_REFRESH_URLS.some((url) => originalRequest.url?.includes(url))) {
      return Promise.reject(error)
    }

    // Don't retry if this request was already retried
    if (originalRequest._retry) {
      forceLogout()
      return Promise.reject(error)
    }

    // If another refresh is already in progress, queue this request
    if (isRefreshing) {
      return new Promise<string>((resolve, reject) => {
        failedQueue.push({ resolve, reject })
      }).then((token) => {
        originalRequest.headers.Authorization = token
        return api(originalRequest)
      })
    }

    // Start token refresh
    originalRequest._retry = true
    isRefreshing = true

    try {
      const response = await api.get<LoginResponse>('/api/user/check_logged')
      const newToken = response.data.token

      // Update localStorage with new token
      localStorage.setItem('auth_token', newToken)

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

      // Process all queued requests with new token
      processQueue(null, newToken)

      // Retry original request with new token
      originalRequest.headers.Authorization = newToken
      return api(originalRequest)
    } catch (refreshError) {
      // Refresh failed - reject all queued requests and logout
      processQueue(refreshError, null)
      forceLogout()
      return Promise.reject(refreshError)
    } finally {
      isRefreshing = false
    }
  },
)

export default api
