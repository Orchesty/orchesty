import type { QueryParams } from '@/types/api'

export type AuditAction = 'Created' | 'Updated' | 'Deleted' | 'Viewed' | 'Executed' | 'Published' | 'Exported'

export interface AuditLogEntry {
  id: string
  timestamp: string
  user: string
  userId: string
  object: string
  objectId: string
  action: AuditAction
  note: string
}

export interface AuditLogQueryParams extends QueryParams {
  search?: string
  timeRange?: string
}

