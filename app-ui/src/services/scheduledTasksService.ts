import type { PaginatedResponse } from '@/types/api'
import type {
  ScheduledTask,
  ScheduledTaskQueryParams,
  ScheduledTaskApiFilter,
  ScheduledTaskApiResponse,
  ScheduledTaskApiItem
} from '@/types/scheduled-tasks'
import api from './api'
import { formatName } from '@/utils/formatName'
import { getNextCronRun } from '@/utils/cronParser'

/**
 * Map API item to ScheduledTask
 */
function mapApiItemToScheduledTask(apiItem: ScheduledTaskApiItem): ScheduledTask {
  // Generate a unique ID from topology ID + node ID
  const id = `${apiItem.topology.id}_${apiItem.node.id}`

  // Map topology.status boolean to our status enum
  const status = apiItem.topology.status ? 'enabled' : 'disabled'

  const topologyName = apiItem.topology.version
    ? `${formatName(apiItem.topology.name)} v.${apiItem.topology.version}`
    : formatName(apiItem.topology.name)

  return {
    id,
    name: formatName(apiItem.node.name),
    nodeId: apiItem.node.id,
    nodeStatus: apiItem.node.status,
    topology: topologyName,
    topologyId: apiItem.topology.id,
    crontab: apiItem.time || null,
    nextRun: apiItem.time && apiItem.node.status && apiItem.topology.status ? getNextCronRun(apiItem.time) : null,
    params: apiItem.node.parameters || '',
    status
  }
}

/**
 * Fetch scheduled tasks with filters, sorting, and pagination
 */
export async function fetchScheduledTasks(
  params: ScheduledTaskQueryParams,
): Promise<PaginatedResponse<ScheduledTask>> {
  // Build the filter object matching API requirements
  const filterObj: ScheduledTaskApiFilter = {
    search: '',
    namespace: 'SCHEDULED_TASK',
    filter: [],
    sorter: [
      {
        column: params.sort || 'id',
        direction: params.order === 'asc' ? 'ASC' : 'DESC'
      }
    ],
    paging: {
      total: 0,
      nextPage: 0,
      previousPage: 0,
      lastPage: 0,
      page: params.page || 1,
      itemsPerPage: params.limit || 50
    }
  }

  // Encode filter as URL parameter
  const encodedFilter = encodeURIComponent(JSON.stringify(filterObj))

  // Make API request
  const response = await api.get<ScheduledTaskApiResponse>(
    `/api/topologies/cron?filter=${encodedFilter}`
  )

  // Only include tasks from enabled topologies
  const mappedItems = response.data.items
    .filter(item => item.topology.status)
    .map(mapApiItemToScheduledTask)

  return {
    data: mappedItems,
    meta: {
      currentPage: response.data.paging.page,
      itemsPerPage: response.data.paging.itemsPerPage,
      totalItems: response.data.paging.total,
      totalPages: response.data.paging.lastPage
    }
  }
}

/**
 * Check if any enabled cron in an enabled topology has no crontab set
 */
export async function checkMisconfiguredCrons(): Promise<boolean> {
  const response = await api.get<ScheduledTaskApiResponse>(
    `/api/topologies/cron`
  )

  return response.data.items.some(
    item => item.node.status && item.topology.status && !item.time
  )
}

/**
 * Update the status (enabled/disabled) of a scheduled task
 */
export async function updateTaskStatus(
  nodeId: string,
  enabled: boolean,
): Promise<void> {
  await api.patch(`/api/nodes/${nodeId}`, {
    enabled
  })
}

/**
 * Update the crontab expression and params of a scheduled task
 */
export async function updateTaskCrontab(
  taskId: string,
  crontab: string,
  params: string,
): Promise<void> {
  // Extract nodeId from the composite taskId (format: topologyId_nodeId)
  const nodeId = taskId.split('_')[1]

  await api.patch(`/api/nodes/${nodeId}`, {
    cron: {
      time: crontab,
      params: params
    }
  })
}
