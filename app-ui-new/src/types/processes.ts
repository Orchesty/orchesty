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

