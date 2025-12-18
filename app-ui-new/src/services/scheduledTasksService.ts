import type { PaginatedResponse } from '@/types/api'
import type { ScheduledTask, ScheduledTaskQueryParams } from '@/types/scheduled-tasks'
import scheduledTasksDataJson from '@/assets/mock-data/scheduled-tasks-data.json'

/**
 * Fetch scheduled tasks with filters, sorting, and pagination
 * Currently returns filtered mock data, will be replaced with API call
 * 
 * @param params - Query parameters for filtering, sorting, and pagination
 * @returns Paginated response with scheduled tasks data
 */
export async function fetchScheduledTasks(
  params: ScheduledTaskQueryParams,
): Promise<PaginatedResponse<ScheduledTask>> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 400))

  // FOR DEVELOPMENT: Filter mock data
  // In production: return axios.get('/api/scheduled-tasks', { params: buildQueryParams(params) })

  let filtered = [...(scheduledTasksDataJson.data as ScheduledTask[])]

  // Apply sorting
  if (params.sort && params.order) {
    filtered.sort((a, b) => {
      const aVal = a[params.sort as keyof ScheduledTask] as number | string
      const bVal = b[params.sort as keyof ScheduledTask] as number | string
      const comparison = aVal > bVal ? 1 : -1
      return params.order === 'asc' ? comparison : -comparison
    })
  }

  // Calculate pagination
  const page = params.page || 1
  const limit = params.limit || 10
  const total = filtered.length
  const totalPages = Math.ceil(total / limit)
  const start = (page - 1) * limit
  const end = start + limit
  const data = filtered.slice(start, end)

  return {
    data,
    pagination: {
      page,
      limit,
      total,
      totalPages,
    },
  }
}

/**
 * Update the status (enabled/disabled) of a scheduled task
 * 
 * @param taskId - ID of the task to update
 * @param enabled - Whether the task should be enabled
 * @returns Updated task
 */
export async function updateTaskStatus(
  taskId: string,
  enabled: boolean,
): Promise<ScheduledTask> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 500))

  // FOR DEVELOPMENT: Simulate update
  // In production: return axios.patch(`/api/scheduled-tasks/${taskId}/status`, { enabled })

  const task = scheduledTasksDataJson.data.find((t) => t.id === taskId)
  if (!task) {
    throw new Error(`Task ${taskId} not found`)
  }

  // Simulate updated task
  return {
    ...task,
    status: enabled ? 'enabled' : 'disabled',
  }
}

/**
 * Update the crontab expression of a scheduled task
 * 
 * @param taskId - ID of the task to update
 * @param crontab - New crontab expression
 * @returns Updated task
 */
export async function updateTaskCrontab(
  taskId: string,
  crontab: string,
): Promise<ScheduledTask> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 500))

  // FOR DEVELOPMENT: Simulate update
  // In production: return axios.patch(`/api/scheduled-tasks/${taskId}/crontab`, { crontab })

  const task = scheduledTasksDataJson.data.find((t) => t.id === taskId)
  if (!task) {
    throw new Error(`Task ${taskId} not found`)
  }

  // Simulate updated task
  return {
    ...task,
    crontab,
    status: task.status === 'not_set' ? 'disabled' : task.status,
  }
}

