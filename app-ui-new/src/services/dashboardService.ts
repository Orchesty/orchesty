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
  LimiterTotalApiResponse,
  LimiterGraphApiResponse,
  LimiterTableApiResponse,
  TrashApiFilter,
  TrashTotalApiResponse,
  TrashGraphApiResponse,
  TrashTableApiResponse,
} from '@/types/dashboard'
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
  dateTo: string
): Promise<{ totalProcesses: number; failedProcesses: number }> {
  const filterObj: ProcessApiFilter = {
    search: null,
    filter: [[{ column: 'created', operator: 'BETWEEN', value: [dateFrom, dateTo] }]],
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
  dateTo: string
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
    params: { filter: JSON.stringify(filterObj) }
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

  // Transform to series format with offset logic
  const FAILED_OFFSET = 1000
  const series: HeatmapSeries[] = Array.from(topologyMap.entries()).map(([topologyId, timeData]) => ({
    name: topologyId, // Will be mapped to topology name in component
    data: Array.from(timeData.entries()).map(([time, metrics]) => {
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

  return { series }
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
}): Promise<LimiterData> {
  const page = params.page || 1
  const itemsPerPage = params.limit || 5

  // Get date ranges: 2x for total/table, 1x for graph
  const doubleRange = convertTimeFilterToDateTimeRangeWithMultiplier(params.timeFilter, 2)
  const normalRange = convertTimeFilterToDateTimeRangeWithMultiplier(params.timeFilter, 1)

  // 1. Fetch total count (2x date range)
  const totalFilter: LimiterApiFilter = {
    search: null,
    filter: [[{
      column: 'created',
      operator: 'BETWEEN',
      value: [doubleRange.from, doubleRange.to]
    }]],
    sorter: [],
    paging: { itemsPerPage: 10, page: 1 }
  }

  const totalResponse = await api.get<LimiterTotalApiResponse>(
    `/api/metrics/limits/total?filter=${encodeURIComponent(JSON.stringify(totalFilter))}`
  )

  // Get totals from response items
  const totalItem = totalResponse.data.items[0] || { count: 0, previousCount: 0 }
  const totalMessages = totalItem.count
  const previousTotal = totalItem.previousCount

  // Calculate vsLastDay (absolute difference)
  const vsLastDay = totalMessages - previousTotal

  // 2. Fetch graph data (normal date range)
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

  const graphResponse = await api.get<LimiterGraphApiResponse>(
    `/api/metrics/limits/graph?filter=${encodeURIComponent(JSON.stringify(graphFilter))}`
  )

  // Transform graph data
  const chartData = {
    categories: graphResponse.data.items.map(item =>
      new Date(item.created).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    ),
    series: graphResponse.data.items.map(item => item.count)
  }

  // 3. Fetch table data (2x date range)
  // Always sort by count on the API side
  const sortColumn = 'count'

  const tableFilter: LimiterApiFilter = {
    search: null,
    filter: [[{
      column: 'created',
      operator: 'BETWEEN',
      value: [doubleRange.from, doubleRange.to]
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

  // Load mappings for name resolution
  const { getNodeName, getTopologyName } = useTopologyNodeMappings()

  // Transform table data
  const tableData: LimiterTableRow[] = tableResponse.data.items.map(item => {
    const currentCount = item.count
    const previousCount = item.previousCount
    const change = previousCount > 0
      ? Math.round(((currentCount - previousCount) / previousCount) * 100)
      : 0

    return {
      connector: getNodeName(item.nodeId),
      topology: getTopologyName(item.topologyId),
      messages: currentCount,
      change
    }
  })

  return {
    totalMessages,
    vsLastDay,
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
 */
export async function fetchTrashData(params: {
  page?: number
  limit?: number
  sortBy?: string
  sortOrder?: 'asc' | 'desc'
  timeFilter: TimeFilter
}): Promise<TrashData> {
  const page = params.page || 1
  const itemsPerPage = params.limit || 5

  // Date ranges: 2x for total, 1x for graph and table
  const doubleRange = convertTimeFilterToDateTimeRangeWithMultiplier(params.timeFilter, 2)
  const normalRange = convertTimeFilterToDateTimeRangeWithMultiplier(params.timeFilter, 1)

  // 1. Fetch total count (2x date range)
  const totalFilter: TrashApiFilter = {
    search: null,
    filter: [[{
      column: 'created',
      operator: 'BETWEEN',
      value: [doubleRange.from, doubleRange.to]
    }]],
    sorter: [],
    paging: { itemsPerPage: 10, page: 1 }
  }

  const totalResponse = await api.get<TrashTotalApiResponse>(
    `/api/metrics/user-tasks/total?filter=${encodeURIComponent(JSON.stringify(totalFilter))}`
  )

  const totalItem = totalResponse.data.items[0] || { count: 0, previousCount: 0 }
  const totalMessages = totalItem.count
  const previousTotal = totalItem.previousCount
  const vsLastDay = totalMessages - previousTotal

  // 2. Fetch graph data (1x date range, unlimited items, sorted by count DESC)
  const graphFilter: TrashApiFilter = {
    search: null,
    filter: [[{
      column: 'created',
      operator: 'BETWEEN',
      value: [normalRange.from, normalRange.to]
    }]],
    sorter: [{ column: 'count', direction: 'DESC' }],
    paging: { itemsPerPage: 9999, page: 1 }
  }

  const graphResponse = await api.get<TrashGraphApiResponse>(
    `/api/metrics/user-tasks/graph?filter=${encodeURIComponent(JSON.stringify(graphFilter))}`
  )

  // Load mappings for name resolution
  const { getNodeName, getTopologyName } = useTopologyNodeMappings()

  // Transform graph data to horizontal bar chart format { x: topologyName, y: count }
  const chartData: Array<{ x: string; y: number }> = graphResponse.data.items.map(item => ({
    x: getTopologyName(item.topologyId),
    y: item.count
  }))

  // 3. Fetch table data (1x date range, always sort by count on API)
  const sortColumn = 'count'

  const tableFilter: TrashApiFilter = {
    search: null,
    filter: [[{
      column: 'created',
      operator: 'BETWEEN',
      value: [normalRange.from, normalRange.to]
    }]],
    sorter: [{
      column: sortColumn,
      direction: (params.sortOrder || 'desc').toUpperCase()
    }],
    paging: { itemsPerPage, page }
  }

  const tableResponse = await api.get<TrashTableApiResponse>(
    `/api/metrics/user-tasks?filter=${encodeURIComponent(JSON.stringify(tableFilter))}`
  )

  // Transform table data with name resolution
  const tableData: TrashTableRow[] = tableResponse.data.items.map(item => ({
    topology: getTopologyName(item.topologyId),
    node: getNodeName(item.nodeId),
    message: item.message || '',
    count: item.count
  }))

  return {
    totalMessages,
    vsLastDay,
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

