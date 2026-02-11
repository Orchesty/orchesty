export interface UserSettings {
  show: boolean
  darkMode: boolean
  language: string
}

export interface User {
  id: string
  email: string
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
