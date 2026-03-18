import type {
  ProcessesChartData,
  LimiterData,
  TrashData,
  TrashTableRow,
  ProcessFilter,
  TimeFilter,
  HeatmapSeries,
  LimiterTableRow,
  LimiterApiFilter,
  LimiterGraphApiResponse,
  LimiterTableApiResponse,
  LimiterTotalApiResponse,
  AppLimiterSetting,
  TrashApiFilter,
  TrashGraphApiResponse,
  TrashTableApiResponse,
  ConnectorHeatmapApiResponse,
  ConnectorHeatmapData,
} from '@/types/dashboard'
import type { TrashApiResponse, TrashApiFilter as TrashItemsApiFilter } from '@/types/trash'
import type { ApplicationInstallApiResponse } from '@/types/applications'
import type {
  ProcessTotalApiResponse,
  ProcessGraphApiResponse,
  ProcessApiFilter,
} from '@/types/processes'
import api from '@/services/api'
import { convertTimeFilterToDateTimeRangeWithMultiplier } from '@/utils/timeRangeConverter'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

/**
 * Fetch total process counts from API
 */
export async function fetchProcessesTotalCounts(
  dateFrom: string,
  dateTo?: string
): Promise<{ totalProcesses: number; failedProcesses: number }> {
  const dateFilter = dateTo
    ? { column: 'created', operator: 'BETWEEN', value: [dateFrom, dateTo] }
    : { column: 'created', operator: 'GTE', value: [dateFrom] }

  const filterObj: ProcessApiFilter = {
    search: null,
    filter: [[dateFilter]],
    sorter: [],
    paging: { itemsPerPage: 10, page: 1 }
  }

  const response = await api.get<ProcessTotalApiResponse>('/api/processes/total', {
    params: { filter: JSON.stringify(filterObj) }
  })

  const data = response.data.items[0]
  return {
    totalProcesses: data?.count || 0,
    failedProcesses: data?.failed || 0
  }
}

/**
 * Fetch processes graph data and transform to heatmap structure
 */
export async function fetchProcessesGraphData(
  filter: ProcessFilter,
  dateFrom: string,
  dateTo: string,
  buckets: number
): Promise<ProcessesChartData> {
  const filterObj: ProcessApiFilter = {
    search: null,
    filter: [[{ column: 'created', operator: 'BETWEEN', value: [dateFrom, dateTo] }]],
    sorter: [
      { column: 'topologyId', direction: 'ASC' },
      { column: 'created', direction: 'ASC' }
    ],
    paging: { itemsPerPage: 9999, page: 1 }
  }

  const response = await api.get<ProcessGraphApiResponse>('/api/processes/graph', {
    params: { filter: JSON.stringify(filterObj), buckets }
  })

  // Group by topology
  const topologyMap = new Map<string, Map<string, { success: number; failed: number }>>()

  response.data.items.forEach(item => {
    if (!topologyMap.has(item.topologyId)) {
      topologyMap.set(item.topologyId, new Map())
    }
    const timeSlot = item.created // Use as-is or format as needed
    const topologyData = topologyMap.get(item.topologyId)!
    topologyData.set(timeSlot, {
      success: item.success,
      failed: item.failed
    })
  })

  // Collect all unique time slots across all topologies and sort them
  const allTimeSlots = new Set<string>()
  topologyMap.forEach(timeData => {
    timeData.forEach((_, time) => allTimeSlots.add(time))
  })
  const xCategories = Array.from(allTimeSlots).sort()

  // Transform to series format with offset logic
  // Fill missing time slots with y=0 so all series have the same x-axis
  const FAILED_OFFSET = 1000
  const series: HeatmapSeries[] = Array.from(topologyMap.entries()).map(([topologyId, timeData]) => ({
    name: topologyId, // Will be mapped to topology name in component
    data: xCategories.map(time => {
      const metrics = timeData.get(time)
      if (!metrics) {
        return { x: time, y: 0, meta: { success: 0, failed: 0, isFailed: false } }
      }
      const isFailed = metrics.failed > 0
      const displayValue = isFailed ? metrics.failed + FAILED_OFFSET : metrics.success

      return {
        x: time,
        y: filter === 'failed' && !isFailed ? 0 : displayValue,
        meta: {
          success: filter === 'failed' && !isFailed ? 0 : metrics.success,
          failed: filter === 'failed' && !isFailed ? 0 : metrics.failed,
          isFailed
        }
      }
    })
  }))

  return { series, xCategories }
}

// API types for /api/applications list response (lightweight)
interface AppListApiResponse {
  activated: boolean
  authorized: boolean
  installed: boolean
  key: string
  name: string
}

interface WorkerListApiResponse {
  applications: AppListApiResponse[]
  name: string
}

// Cache for application limiter settings (shared across calls)
let appLimiterSettingsCache: Map<string, AppLimiterSetting> | null = null

/**
 * Fetch limiter settings for all installed applications.
 * Returns a map of applicationKey -> { name, useLimit, value, time }
 */
export async function fetchApplicationLimiterSettings(
  force = false
): Promise<Map<string, AppLimiterSetting>> {
  if (appLimiterSettingsCache && !force) return appLimiterSettingsCache

  // 1. Get all applications grouped by workers
  const response = await api.get<WorkerListApiResponse[]>('/api/applications')

  // Build a flat list of installed apps with their worker
  const installedApps: Array<{ key: string; name: string; worker: string }> = []
  for (const worker of response.data) {
    for (const app of worker.applications) {
      if (app.installed) {
        installedApps.push({ key: app.key, name: app.name, worker: worker.name })
      }
    }
  }

  // 2. Fetch install details for each installed app in parallel
  const settingsMap = new Map<string, AppLimiterSetting>()

  const results = await Promise.allSettled(
    installedApps.map(async (app) => {
      try {
        const installResponse = await api.get<ApplicationInstallApiResponse>(
          `/api/applications/${app.key}`,
          { params: { sdk: app.worker } }
        )

        // Extract limiter_form from applicationSettings
        const appSettings = installResponse.data.applicationSettings
        let useLimit = false
        let value: number | null = null
        let time: number | null = null

        if (appSettings && appSettings['limiter_form']) {
          const limiterForm = appSettings['limiter_form']
          for (const field of limiterForm.fields || []) {
            if (field.key === 'useLimit') {
              useLimit = field.value === true || field.value === 'true' || field.value === '1'
            } else if (field.key === 'value') {
              value = field.value !== null && field.value !== '' ? Number(field.value) : null
            } else if (field.key === 'time') {
              time = field.value !== null && field.value !== '' ? Number(field.value) : null
            }
          }
        }

        return { key: app.key, setting: { name: app.name, useLimit, value, time } }
      } catch (error) {
        console.warn(`Failed to fetch limiter settings for ${app.key}:`, error)
        return { key: app.key, setting: { name: app.name, useLimit: false, value: null, time: null } }
      }
    })
  )

  for (const result of results) {
    if (result.status === 'fulfilled' && result.value) {
      settingsMap.set(result.value.key, result.value.setting)
    }
  }

  appLimiterSettingsCache = settingsMap
  return settingsMap
}

/**
 * Format a limiter setting into a human-readable string
 */
export function formatLimiterSetting(setting: AppLimiterSetting | undefined): string {
  if (!setting || !setting.useLimit) return 'off'
  if (setting.value === null || setting.time === null) return 'off'
  return `${setting.value} / ${setting.time}s`
}

/**
 * Get limiter card data with pagination and sorting
 */
export async function fetchLimiterData(params: {
  page?: number
  limit?: number
  sortBy?: string
  sortOrder?: 'asc' | 'desc'
  timeFilter: TimeFilter
  appSettings?: Map<string, AppLimiterSetting>
  buckets?: number
}): Promise<LimiterData> {
  const page = params.page || 1
  const itemsPerPage = params.limit || 5

  // Get date range
  const normalRange = convertTimeFilterToDateTimeRangeWithMultiplier(params.timeFilter, 1)

  // 1. Fetch graph data (sorted by created ASC) — graph needs BETWEEN for densify bucketing
  const graphFilter: LimiterApiFilter = {
    search: null,
    filter: [[{
      column: 'created',
      operator: 'BETWEEN',
      value: [normalRange.from, normalRange.to]
    }]],
    sorter: [{ column: 'created', direction: 'ASC' }],
    paging: { itemsPerPage: 9999, page: 1 }
  }

  const buckets = params.buckets || 40
  const graphResponse = await api.get<LimiterGraphApiResponse>(
    '/api/metrics/limits/graph',
    { params: { filter: JSON.stringify(graphFilter), buckets } }
  )

  // Transform graph data
  const seriesValues = graphResponse.data.items.map(item => item.count)
  const chartData = {
    categories: graphResponse.data.items.map(item => item.created),
    series: seriesValues
  }

  // 2. Fetch total current state (sum of last values per node)
  const totalFilter: LimiterApiFilter = {
    search: null,
    filter: [[{
      column: 'created',
      operator: 'GTE',
      value: [normalRange.from]
    }]],
    sorter: [],
    paging: { itemsPerPage: 1, page: 1 }
  }

  const totalResponse = await api.get<LimiterTotalApiResponse>(
    `/api/metrics/limits/total?filter=${encodeURIComponent(JSON.stringify(totalFilter))}`
  )

  const totalMessages = totalResponse.data.items[0]?.count || 0
  const maxMessages = totalResponse.data.items[0]?.maximumCount || 0

  // 3. Fetch table data (per-node breakdown, paginated)
  const sortColumn = 'count'

  const tableFilter: LimiterApiFilter = {
    search: null,
    filter: [[{
      column: 'created',
      operator: 'GTE',
      value: [normalRange.from]
    }]],
    sorter: [{
      column: sortColumn,
      direction: (params.sortOrder || 'desc').toUpperCase()
    }],
    paging: { itemsPerPage, page }
  }

  const tableResponse = await api.get<LimiterTableApiResponse>(
    `/api/metrics/limits?filter=${encodeURIComponent(JSON.stringify(tableFilter))}`
  )

  // Transform table data (raw IDs; names resolved reactively in template)
  const tableData: LimiterTableRow[] = tableResponse.data.items.map(item => {
    const appKey = item.applicationId || ''
    const appSetting = params.appSettings?.get(appKey)

    return {
      nodeId: item.nodeId,
      topologyId: item.topologyId,
      applicationId: appKey,
      connector: item.nodeId,
      topology: item.topologyId,
      application: appKey || '-',
      limitSetting: formatLimiterSetting(appSetting),
      messages: item.count,
      maxMessages: item.maximumCount,
    }
  })

  return {
    totalMessages,
    maxMessages,
    chartData,
    tableData,
    meta: {
      currentPage: tableResponse.data.paging.page,
      totalPages: tableResponse.data.paging.lastPage,
      totalItems: tableResponse.data.paging.total,
      itemsPerPage: tableResponse.data.paging.itemsPerPage
    }
  }
}

/**
 * Get trash card data with pagination and sorting
 * Uses the real /api/user-tasks endpoint for total count (same as Failed Messages page)
 * and metrics endpoints for per-topology chart & table aggregation.
 */
export async function fetchTrashData(params: {
  page?: number
  limit?: number
  sortBy?: string
  sortOrder?: 'asc' | 'desc'
}): Promise<TrashData> {
  const page = params.page || 1
  const itemsPerPage = params.limit || 5

  // 1. Fetch real total count from /api/user-tasks (type=trash), just 1 item to get paging.total
  const realTotalFilter: TrashItemsApiFilter = {
    search: null,
    filter: [
      [{ column: 'type', operator: 'EQ', value: ['trash'] }]
    ],
    sorter: [],
    paging: { itemsPerPage: 1, page: 1 }
  }

  const realTotalResponse = await api.get<TrashApiResponse>(
    `/api/user-tasks?filter=${encodeURIComponent(JSON.stringify(realTotalFilter))}`
  )

  const totalMessages = realTotalResponse.data.paging.total

  // 2. Fetch graph data from metrics (per-topology aggregation for chart)
  const graphFilter: TrashApiFilter = {
    search: null,
    filter: [
      [{ column: 'type', operator: 'EQ', value: ['trash'] }]
    ],
    sorter: [{ column: 'count', direction: 'DESC' }],
    paging: { itemsPerPage: 9999, page: 1 }
  }

  const graphResponse = await api.get<TrashGraphApiResponse>(
    `/api/metrics/user-tasks/graph?filter=${encodeURIComponent(JSON.stringify(graphFilter))}`
  )

  const { ensureLoaded, getTopologyName } = useTopologyNodeMappings()
  await ensureLoaded()

  const chartData: Array<{ x: string; y: number }> = graphResponse.data.items.map(item => ({
    x: getTopologyName(item.topologyId),
    y: item.count
  }))

  // 3. Fetch table data from metrics (per-topology/node aggregation for table)
  const sortColumn = 'count'

  const tableFilter: TrashApiFilter = {
    search: null,
    filter: [
      [{ column: 'type', operator: 'EQ', value: ['trash'] }]
    ],
    sorter: [{
      column: sortColumn,
      direction: (params.sortOrder || 'desc').toUpperCase()
    }],
    paging: { itemsPerPage, page }
  }

  const tableResponse = await api.get<TrashTableApiResponse>(
    `/api/metrics/user-tasks?filter=${encodeURIComponent(JSON.stringify(tableFilter))}`
  )

  // Transform table data (raw IDs; names resolved reactively in template)
  const tableData: TrashTableRow[] = tableResponse.data.items.map(item => ({
    topologyId: item.topologyId,
    nodeId: item.nodeId,
    topology: item.topologyId,
    node: item.nodeId,
    message: item.message || '',
    count: item.count
  }))

  return {
    totalMessages,
    chartData,
    tableData,
    meta: {
      currentPage: tableResponse.data.paging.page,
      totalPages: tableResponse.data.paging.lastPage,
      totalItems: tableResponse.data.paging.total,
      itemsPerPage: tableResponse.data.paging.itemsPerPage
    }
  }
}

/**
 * Fetch connector heatmap data grouped by nodeId and time bin.
 * Returns series sorted by applicationId so connectors of the same app are adjacent.
 */
export async function fetchConnectorHeatmapData(
  filter: ProcessFilter,
  dateFrom: string,
  dateTo: string,
  buckets: number
): Promise<ConnectorHeatmapData> {
  const filterObj = {
    search: null,
    filter: [[{ column: 'created', operator: 'BETWEEN', value: [dateFrom, dateTo] }]],
    sorter: [
      { column: 'nodeId', direction: 'ASC' },
      { column: 'created', direction: 'ASC' }
    ],
    paging: { itemsPerPage: 9999, page: 1 }
  }

  const response = await api.get<ConnectorHeatmapApiResponse>(
    '/api/metrics/connectors/heatmap',
    { params: { filter: JSON.stringify(filterObj), buckets } }
  )

  // Group by nodeId, track applicationId per node
  const nodeMap = new Map<string, Map<string, { success: number; failed: number }>>()
  const nodeAppMap = new Map<string, string>() // nodeId -> applicationId
  let totalRequests = 0
  let totalFailed = 0

  response.data.items.forEach(item => {
    if (!nodeMap.has(item.nodeId)) {
      nodeMap.set(item.nodeId, new Map())
    }
    if (!nodeAppMap.has(item.nodeId)) {
      nodeAppMap.set(item.nodeId, item.applicationId || '')
    }

    const timeData = nodeMap.get(item.nodeId)!
    timeData.set(item.created, {
      success: item.success,
      failed: item.failed
    })

    totalRequests += item.success + item.failed
    totalFailed += item.failed
  })

  // Collect all unique time slots and sort
  const allTimeSlots = new Set<string>()
  nodeMap.forEach(timeData => {
    timeData.forEach((_, time) => allTimeSlots.add(time))
  })
  const xCategories = Array.from(allTimeSlots).sort()

  // Build series with offset logic (same as processes heatmap)
  const FAILED_OFFSET = 1000

  // Sort nodes by applicationId first, then by nodeId for stable ordering
  const sortedNodes = Array.from(nodeMap.entries()).sort((a, b) => {
    const appA = nodeAppMap.get(a[0]) || ''
    const appB = nodeAppMap.get(b[0]) || ''
    if (appA !== appB) return appA.localeCompare(appB)
    return a[0].localeCompare(b[0])
  })

  const series: HeatmapSeries[] = sortedNodes.map(([nodeId, timeData]) => ({
    name: nodeId, // Will be resolved to connector name in component
    data: xCategories.map(time => {
      const metrics = timeData.get(time)
      if (!metrics) {
        return { x: time, y: 0, meta: { success: 0, failed: 0, isFailed: false } }
      }
      const isFailed = metrics.failed > 0
      const displayValue = isFailed ? metrics.failed + FAILED_OFFSET : metrics.success

      return {
        x: time,
        y: filter === 'failed' && !isFailed ? 0 : displayValue,
        meta: {
          success: filter === 'failed' && !isFailed ? 0 : metrics.success,
          failed: filter === 'failed' && !isFailed ? 0 : metrics.failed,
          isFailed
        }
      }
    })
  }))

  return { series, xCategories, totalRequests, totalFailed, nodeAppMap }
}

