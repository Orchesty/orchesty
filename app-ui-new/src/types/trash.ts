import type { QueryParams } from './common'

export interface TrashItem {
  id: string
  topology: string
  topologyId: string
  node: string
  nodeId: string
  correlationId: string
  timestamp: string
  resultMessage: string
  headers: Record<string, unknown>
  body: Record<string, unknown>
}

export interface TrashQueryParams {
  page?: number
  perPage?: number
  sortBy?: string
  sortOrder?: 'asc' | 'desc'
  correlationId?: string
  node?: string
  topology?: string
  timeRange?: string
  dateFrom?: string
  dateTo?: string
}

