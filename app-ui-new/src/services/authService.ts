import api from './api'
import type { LoginRequest, LoginResponse } from '@/types/auth'

/**
 * Login with email and password
 * @param email - User email
 * @param password - User password
 * @returns Login response with token and user data
 */
export async function login(email: string, password: string): Promise<LoginResponse> {
  const requestData: LoginRequest = {
    email,
    password,
  }

  const response = await api.post<LoginResponse>('/api/user/login', requestData)
  return response.data
}

/**
 * Refresh authentication token
 * Uses the refreshToken cookie automatically sent by browser
 * @returns New token and user data
 */
export async function refreshToken(): Promise<LoginResponse> {
  const response = await api.get<LoginResponse>('/api/user/check_logged')
  return response.data
}

/**
 * Request a password reset email
 * @param email - User email to send reset instructions to
 */
export async function resetPassword(email: string): Promise<void> {
  await api.post('/api/user/reset_password', { email })
}

/**
 * Verify a password reset token
 * @param token - The reset token from the URL
 * @returns Object with the user's email if token is valid
 */
export async function verifyResetToken(token: string): Promise<{ email: string }> {
  const response = await api.post<{ email: string }>(`/api/user/${token}/verify`)
  return response.data
}

/**
 * Set a new password using a reset token
 * @param token - The reset token from the URL
 * @param password - The new password
 */
export async function setNewPassword(token: string, password: string): Promise<void> {
  await api.post(`/api/user/${token}/set_password`, { password })
}

/**
 * Check if any users exist in the system
 * Used to determine whether to show setup or login page
 */
export async function checkUsersExist(): Promise<boolean> {
  const response = await api.get<{ hasUser: boolean }>('/api/user/exists')
  return response.data.hasUser
}

/**
 * Create initial admin user during first-time setup (public endpoint, no auth required)
 */
export async function registerUser(email: string, password: string): Promise<void> {
  await api.post('/api/user/setup', { email, password })
}

/**
 * Logout user
 * Clears local authentication data
 * Note: Backend logout endpoint can be added here if available
 */
export async function logout(): Promise<void> {
  // Clear local storage
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')

  // Optional: Call backend logout endpoint if available
  // await api.post('/api/user/logout')
}
