import type { QueryParams } from './api'

export type ScheduledTaskStatus = 'enabled' | 'disabled' | 'not_set'

export interface ScheduledTask {
  id: string
  name: string
  topology: string
  topologyId: string
  crontab: string | null
  status: ScheduledTaskStatus
}

export interface ScheduledTaskQueryParams extends QueryParams {
  // Future: filters can be added here
}

