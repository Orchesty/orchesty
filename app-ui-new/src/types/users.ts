import type { QueryParams } from './api'

export type UserStatus = 'active' | 'inactive'
export type UserRole = 'Admin' | 'Developer' | 'Viewer'

export interface User {
  id: string
  name: string
  email: string
  role: UserRole
  status: UserStatus
  groups: string[] // group IDs
}

export interface UserQueryParams extends QueryParams {
  status?: UserStatus
  search?: string
}

export interface Group {
  id: string
  name: string
  modules: string[]
  users: string[] // user IDs
}

export interface GroupQueryParams extends QueryParams {
  search?: string
}

