import type { QueryParams } from './datagrid'

// Worker Types
export interface Worker {
  id: string
  name: string
  url: string
  headers: Record<string, string>
}

export interface WorkerQueryParams extends QueryParams {
  search?: string
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

