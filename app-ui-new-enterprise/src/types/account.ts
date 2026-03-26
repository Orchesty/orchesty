export interface NotificationSettings {
  id: string
  label: string
  description: string
  enabled: boolean
}

export interface ProfileUpdateData {
  username?: string
}

export interface PasswordUpdateData {
  currentPassword: string
  newPassword: string
}

