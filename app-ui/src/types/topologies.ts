import type { QueryParams } from './api'

export interface Topology {
  id: string
  name: string
  processesRun: number
  failedProcesses: number
  lastRunTime: string
  lastRunStatus: 'success' | 'running' | 'failed' | 'none'
  enabled: boolean
}

export type TopologyStatus = 'all' | 'enabled' | 'with-activity'

export interface TopologyQueryParams extends QueryParams {
  status?: TopologyStatus
  dateFrom?: string
  dateTo?: string
  timeRange?: string // @deprecated - use dateFrom/dateTo instead
}

// API Response Types
export interface TopologyApiItem {
  topologyId: string
  created: string
  count: number
  failedCount: number
  status: string
  id: string
}

export interface TopologyApiResponse {
  filter: unknown[]
  items: TopologyApiItem[]
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

export interface TopologyApiFilter {
  search: string | null
  filter: Array<Array<{ column: string; operator: string; value: unknown[] }>>
  sorter: Array<{ column: string; direction: string }>
  paging: {
    itemsPerPage: number
    page: number
  }
}
