import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import type { User } from '@/types/auth'
import * as authService from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  // State
  const token = ref<string | null>(null)
  const user = ref<User | null>(null)
  const refreshIntervalId = ref<number | null>(null)

  // Computed
  const isAuthenticated = computed(() => !!token.value && !!user.value)

  // Actions
  /**
   * Start automatic token refresh
   */
  function startTokenRefresh(): void {
    // Clear any existing interval
    stopTokenRefresh()

    // Refresh every 5 minutes (300000 ms)
    refreshIntervalId.value = window.setInterval(async () => {
      await refreshAuthToken()
    }, 300000)
  }

  /**
   * Stop automatic token refresh
   */
  function stopTokenRefresh(): void {
    if (refreshIntervalId.value !== null) {
      clearInterval(refreshIntervalId.value)
      refreshIntervalId.value = null
    }
  }

  /**
   * Refresh the authentication token
   */
  async function refreshAuthToken(): Promise<void> {
    try {
      const response = await authService.refreshToken()

      // Update token in state and localStorage
      token.value = response.token
      localStorage.setItem('auth_token', response.token)

      // User data typically doesn't change, but update if provided
      if (response.email && response.id) {
        user.value = {
          id: response.id,
          email: response.email,
          settings: response.settings,
        }
        localStorage.setItem('auth_user', JSON.stringify(user.value))
      }

      console.log('Token refreshed successfully')
    } catch (error) {
      console.error('Token refresh failed:', error)
      // If refresh fails, logout user
      await logout()
    }
  }

  /**
   * Login user with email and password
   */
  async function login(email: string, password: string): Promise<void> {
    const response = await authService.login(email, password)

    // Store token and user in state
    token.value = response.token
    user.value = {
      id: response.id,
      email: response.email,
      settings: response.settings,
    }

    // Persist to localStorage
    localStorage.setItem('auth_token', response.token)
    localStorage.setItem('auth_user', JSON.stringify(user.value))

    // Start token refresh timer
    startTokenRefresh()
  }

  /**
   * Logout user and clear session
   */
  async function logout(): Promise<void> {
    // Stop token refresh timer
    stopTokenRefresh()

    await authService.logout()

    // Clear state
    token.value = null
    user.value = null
  }

  /**
   * Initialize auth from localStorage
   * Call this on app start to restore session
   */
  function initializeAuth(): void {
    const storedToken = localStorage.getItem('auth_token')
    const storedUser = localStorage.getItem('auth_user')

    if (storedToken && storedUser) {
      try {
        token.value = storedToken
        user.value = JSON.parse(storedUser)

        // Start token refresh timer for existing session
        startTokenRefresh()
      } catch (error) {
        console.error('Failed to parse stored user data:', error)
        // Clear invalid data
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_user')
      }
    }
  }

  return {
    // State
    token,
    user,
    // Computed
    isAuthenticated,
    // Actions
    login,
    logout,
    initializeAuth,
    refreshAuthToken,
    startTokenRefresh,
    stopTokenRefresh,
  }
})
