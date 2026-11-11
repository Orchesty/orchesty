export interface NotificationSettings {
  id: string
  label: string
  description: string
  enabled: boolean
}

export interface NotificationSubscription {
  id?: string
  tenant_id?: string
  user_id?: string
  subject_type?: string
  subject_id?: string
  event_type: string
  channel: string
  enabled: boolean
  filters?: NotificationSubFilters
}

export interface NotificationSubFilters {
  topology_names?: string[]
}

export interface NotificationPreset {
  id: string
  label: string
  description: string
}

export interface ProfileUpdateData {
  username?: string
}

export interface PasswordUpdateData {
  currentPassword: string
  newPassword: string
}

