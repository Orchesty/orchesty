import type { QueryParams } from './dashboard'

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

export interface LogQueryParams extends QueryParams {
  search?: string
  timeMargin?: number
  severity?: LogSeverity | null
  topology?: string | null
  timeRange?: string
}

