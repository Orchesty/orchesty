import api from './api'
import type { PasswordUpdateData, NotificationSettings } from '@/types/account'
import type { UserSettings } from '@/types/auth'

/**
 * Save user settings (profile data like username)
 */
export async function updateProfile(userId: string, settings: UserSettings): Promise<void> {
  await api.post(`/api/user/${userId}/saveSettings`, { settings })
}

/**
 * Update user password
 */
export async function updatePassword(data: PasswordUpdateData): Promise<void> {
  await api.post('/api/user/change_password', {
    password: data.newPassword,
    old_password: data.currentPassword,
  })
}

/**
 * Update notification preferences (not yet connected to backend)
 */
export async function updateNotifications(_settings: NotificationSettings[]): Promise<void> {
  // TODO: Connect to backend when notification API is available
}
