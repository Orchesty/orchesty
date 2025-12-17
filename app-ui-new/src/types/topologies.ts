import type { QueryParams } from './api'

export interface Topology {
  id: string
  name: string
  processesRun: number
  failedProcesses: number
  lastRunTime: string
  lastRunStatus: 'success' | 'running' | 'failed'
}

export type TopologyStatus = 'all' | 'success' | 'running' | 'failed'

export interface TopologyQueryParams extends QueryParams {
  status?: TopologyStatus
  timeRange?: string
}
