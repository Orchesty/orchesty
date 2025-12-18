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
  search?: string
  application?: string
  dateFrom?: string
  dateTo?: string
}

export interface ConnectorErrorRecord {
  timestamp: string
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

