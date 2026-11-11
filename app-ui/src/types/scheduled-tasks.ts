import type { QueryParams } from './api'

export type ScheduledTaskStatus = 'enabled' | 'disabled'

export interface ScheduledTask {
  id: string
  name: string
  nodeId: string
  nodeStatus: boolean  // Node enabled/disabled state (for toggle)
  topology: string
  topologyId: string
  crontab: string | null
  nextRun: Date | null
  params: string  // Cron parameters
  status: ScheduledTaskStatus  // Topology enabled/disabled state (for badge)
}

export interface ScheduledTaskQueryParams extends QueryParams {
  // Future: filters can be added here
}

// API-specific types
export interface ScheduledTaskApiFilter {
  search: string
  namespace: string
  filter: unknown[]
  sorter: Array<{
    column: string
    direction: string
  }>
  paging: {
    total: number
    nextPage: number
    previousPage: number
    lastPage: number
    page: number
    itemsPerPage: number
  }
}

export interface ScheduledTaskApiItem {
  node: {
    id: string
    name: string
    status: boolean  // Node enabled/disabled state
    parameters: string  // Cron parameters
  }
  time: string  // Crontab expression
  topology: {
    id: string
    name: string
    status: boolean  // Topology enabled/disabled state
    version: number
  }
}

export interface ScheduledTaskApiResponse {
  items: ScheduledTaskApiItem[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
}
