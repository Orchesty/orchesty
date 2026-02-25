export type LogSeverity = 'error' | 'warning' | 'info' | 'debug'

export interface LogEntry {
  id: string
  timestamp: string
  topology: string
  topologyId: string
  node: string
  nodeId: string
  correlationId: string
  severity: LogSeverity
  message: string
  additionalContext?: Record<string, unknown>
}

export interface LogQueryParams {
  page?: number
  perPage?: number
  sortBy?: string
  sortOrder?: 'asc' | 'desc'
  search?: string
  correlationId?: string
  timeMargin?: number
  severity?: LogSeverity | null
  topology?: string | null
  node?: string | string[] | null
  timeRange?: string
}

// API response types
export interface LogApiItem {
  nodeId: string
  topologyId: string
  correlationId: string
  created: string
  severity: string
  message: string
  id: string
}

export interface LogApiResponse {
  filter: unknown[]
  items: LogApiItem[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  search: string | null
  sorter: Array<{ column: string; direction: string }>
}

export interface LogApiFilter {
  search: string | null
  filter: Array<Array<{ column: string; operator: string; value: unknown[] }>>
  sorter: Array<{ column: string; direction: string }>
  paging: {
    itemsPerPage: number
    page: number
  }
}
