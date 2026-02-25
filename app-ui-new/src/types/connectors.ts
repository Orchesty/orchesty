import type { QueryParams } from './api'

export interface Connector {
  id: string
  name: string
  application: string
  avgRequestTime: number // in milliseconds
  requests: number
  errors400: number
  errors500: number
  lastRequestStatus: number // HTTP status code (200, 400, 500, etc.)
  status: 'ok' | 'errors'
}

export type ConnectorStatus = 'all' | 'ok' | 'errors'

export interface ConnectorQueryParams extends QueryParams {
  status?: ConnectorStatus
  node?: string
  application?: string
  dateFrom?: string
  dateTo?: string
}

export interface ConnectorErrorRecord {
  timestamp: string
  topologyId: string
  topology: string
  code: number
  message: string
}

export interface ConnectorDetail {
  connector: Connector
  errors400: number
  errors500: number
  totalRequests: number
  lastRequestStatus: number
  errorRecords: ConnectorErrorRecord[]
}

// API Response Types
export interface ConnectorApiItem {
  nodeId: string
  topologyId: string
  applicationId: string
  count: number
  duration: number
  status400: number
  status500: number
  lastStatus: number
  id: string
}

export interface ConnectorApiResponse {
  filter: unknown[]
  items: ConnectorApiItem[]
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

export interface ConnectorApiFilter {
  search: string | null
  filter: Array<Array<{ column: string; operator: string; value: unknown[] }>>
  sorter: Array<{ column: string; direction: string }>
  paging: {
    itemsPerPage: number
    page: number
  }
}

// Graph API types
export interface ConnectorGraphApiItem {
  created: string
  status200: number
  status400: number
  status500: number
}

export interface ConnectorGraphApiResponse {
  filter: unknown[]
  items: ConnectorGraphApiItem[]
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

// Error records API types
export interface ConnectorErrorApiItem {
  nodeId: string
  topologyId: string
  applicationId: string
  created: string
  status: number
  message: string | null
  id: string
}

export interface ConnectorErrorApiResponse {
  filter: unknown[]
  items: ConnectorErrorApiItem[]
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
