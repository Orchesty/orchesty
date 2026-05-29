import type { QueryParams } from './api'

export interface Connector {
  nodeIds: string[]
  name: string
  application: string
  topologyIds: string[]
  avgRequestTime: number // in milliseconds
  requests: number
  errors400: number
  errors500: number
  lastRequestStatus: number // HTTP status code (200, 400, 500, etc.)
  status: 'ok' | 'errors' | 'none'
}

export type ConnectorStatus = 'all' | 'with-activity' | 'with-errors'

export interface ConnectorGroupApiItem {
  name: string
  application: string
  type: string
  nodeIds: string[]
  topologyIds: string[]
}

export interface ConnectorGroupsApiResponse {
  items: ConnectorGroupApiItem[]
}

export interface ConnectorQueryParams extends QueryParams {
  status?: ConnectorStatus
  node?: string | string[]
  application?: string
  dateFrom?: string
  dateTo?: string
}

export interface ConnectorErrorRecord {
  id: string
  timestamp: string
  topologyId: string
  topology: string
  nodeId: string
  applicationId: string
  correlationId: string
  userId: string
  duration: number
  code: number
  message: string
}

export interface ConnectorDetail {
  connector: Connector
  errors400: number
  errors500: number
  totalRequests: number
  lastRequestStatus: number
  lastRequestTime: number
  avgRequestTime: number
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
  correlationId: string
  userId: string
  created: string
  duration: number
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
