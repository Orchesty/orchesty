import type { QueryParams } from './api'

export interface Process {
  id: string
  topology: string
  topologyId: string
  startTime: string
  duration: number // in seconds
  status: 'running' | 'completed' | 'failed'
  errorMessage?: string
}

export type ProcessStatus = 'all' | 'completed' | 'running' | 'failed'

export interface ProcessQueryParams extends QueryParams {
  status?: ProcessStatus
  topology?: string
  topologyIds?: string[]
  dateFrom?: string
  dateTo?: string
}

export interface ProcessConnector {
  connector: string
  application: string
  called: number
  errors400: number
  errors500: number
}

export interface ProcessTrashItem {
  whereItFailed: string
  errorMessage: string
}

export interface ProcessAuditDetail {
  processId: string
  topology: string
  corelId: string
  startTime: string
  endTime: string
  status: 'running' | 'completed' | 'failed'
  connectors: ProcessConnector[]
  trashCount: number
  trashItems: ProcessTrashItem[]
}

// API Response Types for Total Counts
export interface ProcessTotalApiItem {
  count: number
  failed: number
}

export interface ProcessTotalApiResponse {
  filter: unknown[]
  items: ProcessTotalApiItem[]
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

// API Response Types for Graph Data
export interface ProcessGraphApiItem {
  created: string
  topologyId: string
  success: number
  failed: number
}

export interface ProcessGraphApiResponse {
  filter: unknown[]
  items: ProcessGraphApiItem[]
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

// API Response Types for Table Data
export interface ProcessApiItem {
  topologyId: string
  created: string
  status: string // "RUNNING", "COMPLETED", "FAILED"
  duration: number // in milliseconds
  messages: string[]
  id: string
}

export interface ProcessApiResponse {
  filter: unknown[]
  items: ProcessApiItem[]
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

export interface ProcessApiFilter {
  search: string | null
  filter: Array<Array<{ column: string; operator: string; value: unknown[] }>>
  sorter: Array<{ column: string; direction: string }>
  paging: {
    itemsPerPage: number
    page: number
  }
}

// Connector API for Process Audit
export interface ProcessAuditConnectorApiItem {
  nodeId: string
  topologyId: string
  applicationId: string | null
  count: number
  duration: number
  status400: number
  status500: number
  lastStatus: number
  id: string
}

export interface ProcessAuditConnectorApiResponse {
  filter: unknown[]
  items: ProcessAuditConnectorApiItem[]
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

// Trash API for Process Audit
export interface ProcessAuditTrashApiItem {
  id: string
  nodeId: string
  topologyId: string
  correlationId: string
  created: string
  body: string // JSON string
  headers: Record<string, unknown>
}

export interface ProcessAuditTrashApiResponse {
  filter: unknown[]
  items: ProcessAuditTrashApiItem[]
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

// API Filter for Process Audit queries
export interface ProcessAuditApiFilter {
  search: string | null
  filter: Array<Array<{ column: string; operator: string; value: unknown[] }>>
  sorter: Array<{ column: string; direction: string }>
  paging: {
    itemsPerPage: number
    page: number
  }
}
