import type { ProfileUpdateData, PasswordUpdateData, NotificationSettings } from '@/types/account'

/**
 * Simulate API delay
 */
const simulateDelay = () => {
  const delay = Math.floor(Math.random() * 300) + 500 // 500-800ms
  return new Promise((resolve) => setTimeout(resolve, delay))
}

/**
 * Simulate random error (10% chance)
 */
const simulateRandomError = () => {
  if (Math.random() < 0.1) {
    throw new Error('Network error occurred')
  }
}

/**
 * Update user profile (username)
 * Email is readonly and cannot be changed
 */
export async function updateProfile(data: ProfileUpdateData): Promise<void> {
  await simulateDelay()
  simulateRandomError()
  
  console.log('Profile updated:', data)
  // In production: return axios.put('/api/account/profile', data)
}

/**
 * Update user password
 * Validates that new password and confirm password match
 */
export async function updatePassword(data: PasswordUpdateData): Promise<void> {
  await simulateDelay()
  
  // Validate passwords match
  if (data.newPassword !== data.confirmPassword) {
    throw new Error('Passwords do not match')
  }
  
  // Validate password length
  if (data.newPassword.length < 8) {
    throw new Error('Password must be at least 8 characters')
  }
  
  simulateRandomError()
  
  console.log('Password updated')
  // In production: return axios.put('/api/account/password', data)
}

/**
 * Update notification preferences
 */
export async function updateNotifications(settings: NotificationSettings[]): Promise<void> {
  await simulateDelay()
  simulateRandomError()
  
  console.log('Notifications updated:', settings)
  // In production: return axios.put('/api/account/notifications', settings)
}

