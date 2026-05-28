export interface UserSettings {
  show?: boolean
  darkMode?: boolean
  language?: string
  username?: string
}

export interface User {
  id: string
  email: string
  picture?: string
  isOrgMember?: boolean
  settings: UserSettings
}

export interface LoginRequest {
  email: string
  password: string
}

export interface LoginResponse {
  email: string
  id: string
  settings: UserSettings
  token: string
}
