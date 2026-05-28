import type { QueryParams } from './api'

// Worker Types
export type WorkerType = 'http' | 'tunnel'

export interface Worker {
  id: string
  name: string
  url: string
  type: WorkerType
  headers: Record<string, string>
}

export interface WorkerQueryParams extends QueryParams {
  search?: string
}

// API types (what backend sends/expects)
export interface WorkerHeaderItem {
  key: string
  value: string
}

export interface WorkerApiResponse {
  id: string
  name: string
  url: string
  type: WorkerType
  headers: WorkerHeaderItem[]
}

export interface WorkersListResponse {
  filter: unknown[]
  items: WorkerApiResponse[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
}

// Token Types
export interface TokenScope {
  id: string
  label: string
  description?: string
}

export interface Token {
  id: string
  name: string
  created: string // ISO date string
  expiration: string | null // ISO date string or null for no expiration
  scopes: string[]
  tokenValue?: string // Only included on creation
}

export interface TokenQueryParams extends QueryParams {
  search?: string
}

// Audit Entity Types
export interface AuditEntityAttribute {
  name: string
  description: string
}

export interface AuditEntity {
  id: string
  name: string
  attributes: AuditEntityAttribute[]
}

export interface AuditEntityQueryParams extends QueryParams {
  search?: string
}

// Audit Entity API types (what backend sends/expects)
export interface AuditEntityFieldApi {
  key: string
  name: string
}

export interface AuditEntityApiResponse {
  id: string
  key: string
  name: string
  fields: AuditEntityFieldApi[]
}

export interface AuditEntitiesListResponse {
  items: AuditEntityApiResponse[]
  filter: unknown[]
  paging: {
    page: number
    itemsPerPage: number
    total: number
    nextPage: number
    lastPage: number
    previousPage: number
  }
}

// Token API types (what backend sends/expects)
export interface TokenApiResponse {
  id: string
  user: string
  key: string
  expireAt: string | null
  scopes: string[] | string  // Array in list, comma-separated string in create/delete
  created: string
}

export interface TokensListResponse {
  filter: unknown[]
  items: TokenApiResponse[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  search: string
  sorter: Array<{ column: string; direction: string }>
}
