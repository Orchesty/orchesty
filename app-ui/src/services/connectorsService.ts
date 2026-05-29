import type { PaginatedResponse } from '@/types/api'
import type {
  Connector,
  ConnectorQueryParams,
  ConnectorDetail,
  ConnectorErrorRecord,
  ConnectorApiItem,
  ConnectorApiResponse,
  ConnectorApiFilter,
  ConnectorGraphApiItem,
  ConnectorGraphApiResponse,
  ConnectorErrorApiItem,
  ConnectorErrorApiResponse,
  ConnectorGroupsApiResponse,
} from '@/types/connectors'
import type { TimeFilter } from '@/types/dashboard'
import api from '@/services/api'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApi } from '@/utils/timeRangeConverter'

/**
 * Map graph API response to chart data format
 */
function mapGraphApiToChartData(items: ConnectorGraphApiItem[]): {
  categories: number[]
  errors400: number[]
  errors500: number[]
} {
  return {
    categories: items.map(item => new Date(item.created).getTime()),
    errors400: items.map(item => item.status400),
    errors500: items.map(item => item.status500)
  }
}

/**
 * Map error API item to ConnectorErrorRecord
 */
function mapErrorApiItemToRecord(
  apiItem: ConnectorErrorApiItem,
): ConnectorErrorRecord {
  return {
    id: apiItem.id,
    timestamp: apiItem.created,
    topologyId: apiItem.topologyId,
    topology: apiItem.topologyId,
    nodeId: apiItem.nodeId,
    applicationId: apiItem.applicationId,
    correlationId: apiItem.correlationId || '',
    userId: apiItem.userId || '',
    duration: apiItem.duration || 0,
    code: apiItem.status,
    message: apiItem.message || ''
  }
}

/**
 * Fetch grouped connector nodes merged with time-filtered metrics.
 * Backend returns connectors grouped by name+application; frontend aggregates metrics per group.
 */
export async function fetchConnectors(
  params: ConnectorQueryParams,
): Promise<PaginatedResponse<Connector>> {
  const metricsFilter: ConnectorApiFilter = {
    search: null,
    filter: [],
    sorter: [],
    paging: { itemsPerPage: 9999, page: 1 },
  }

  if (params.dateFrom) {
    metricsFilter.filter.push([params.dateTo
      ? { column: 'created', operator: 'BETWEEN', value: [params.dateFrom, params.dateTo] }
      : { column: 'created', operator: 'GTE', value: [params.dateFrom] }
    ])
  }

  const [groupsResponse, metricsResponse] = await Promise.all([
    api.get<ConnectorGroupsApiResponse>('/api/nodes/connectors'),
    api.get<ConnectorApiResponse>('/api/metrics/connectors/overview', {
      params: { filter: JSON.stringify(metricsFilter) },
    }),
  ])

  const metricsMap = new Map<string, ConnectorApiItem>()
  for (const item of metricsResponse.data.items) {
    metricsMap.set(item.nodeId, item)
  }

  let connectors: Connector[] = groupsResponse.data.items.map(group => {
    let totalRequests = 0
    let totalErrors400 = 0
    let totalErrors500 = 0
    let totalDuration = 0
    let lastStatus = 0

    for (const nodeId of group.nodeIds) {
      const m = metricsMap.get(nodeId)
      if (!m) continue
      totalRequests += m.count
      totalErrors400 += m.status400
      totalErrors500 += m.status500
      totalDuration += m.duration * m.count
      if (m.lastStatus) lastStatus = m.lastStatus
    }

    const avgRequestTime = totalRequests > 0 ? Math.round(totalDuration / totalRequests) : 0
    const hasErrors = totalErrors400 > 0 || totalErrors500 > 0

    return {
      nodeIds: group.nodeIds,
      name: group.name,
      application: group.application || '',
      topologyIds: group.topologyIds,
      avgRequestTime,
      requests: totalRequests,
      errors400: totalErrors400,
      errors500: totalErrors500,
      lastRequestStatus: lastStatus,
      status: totalRequests > 0 ? (hasErrors ? 'errors' : 'ok') : 'none' as const,
    }
  })

  if (params.application) {
    connectors = connectors.filter(c => c.application === params.application)
  }

  if (params.status === 'with-activity') {
    connectors = connectors.filter(c => c.requests > 0)
  } else if (params.status === 'with-errors') {
    connectors = connectors.filter(c => c.errors400 > 0 || c.errors500 > 0)
  }

  const sortField = params.sort || 'name'
  const sortDir = params.order === 'desc' ? -1 : 1
  connectors.sort((a, b) => {
    const av = a[sortField as keyof Connector] ?? ''
    const bv = b[sortField as keyof Connector] ?? ''
    if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * sortDir
    return String(av).localeCompare(String(bv)) * sortDir
  })

  const page = params.page || 1
  const limit = params.limit || 25
  const totalItems = connectors.length
  const totalPages = Math.max(1, Math.ceil(totalItems / limit))
  const paged = connectors.slice((page - 1) * limit, page * limit)

  return {
    data: paged,
    meta: { totalItems, totalPages, currentPage: page, itemsPerPage: limit },
  }
}

/**
 * Fetch connector detail with error statistics.
 * Filters by nodeId array so all nodes with the same name are included.
 */
export async function fetchConnectorDetail(
  nodeIds: string[],
  timeFilter: TimeFilter,
): Promise<ConnectorDetail> {
  const dateRange = convertTimeFilterToDateTimeRange(timeFilter)
  const dateFrom = formatDateTimeForApi(dateRange.from) || ''

  const overviewFilter: ConnectorApiFilter = {
    search: null,
    filter: [
      [{ column: 'created', operator: 'GTE', value: [dateFrom] }],
      [{ column: 'nodeId', operator: 'EQ', value: nodeIds }]
    ],
    sorter: [],
    paging: { itemsPerPage: 9999, page: 1 }
  }

  const lastRecordFilter: ConnectorApiFilter = {
    search: null,
    filter: [
      [{ column: 'created', operator: 'GTE', value: [dateFrom] }],
      [{ column: 'nodeId', operator: 'EQ', value: nodeIds }]
    ],
    sorter: [{ column: 'created', direction: 'DESC' }],
    paging: { itemsPerPage: 1, page: 1 }
  }

  const [overviewRes, lastRecordRes] = await Promise.all([
    api.get<ConnectorApiResponse>('/api/metrics/connectors/overview', {
      params: { filter: JSON.stringify(overviewFilter) }
    }),
    api.get<ConnectorErrorApiResponse>('/api/metrics/connectors', {
      params: { filter: JSON.stringify(lastRecordFilter) }
    }),
  ])

  const items = overviewRes.data.items
  const lastRecord = lastRecordRes.data.items[0]

  if (items.length === 0) {
    return {
      connector: {
        nodeIds,
        name: '',
        application: '',
        topologyIds: [],
        avgRequestTime: 0,
        requests: 0,
        errors400: 0,
        errors500: 0,
        lastRequestStatus: 0,
        status: 'ok'
      },
      errors400: 0,
      errors500: 0,
      totalRequests: 0,
      lastRequestStatus: 0,
      lastRequestTime: 0,
      avgRequestTime: 0,
      errorRecords: [],
    }
  }

  let totalRequests = 0
  let totalErrors400 = 0
  let totalErrors500 = 0
  let totalDuration = 0
  let lastStatus = 0

  for (const item of items) {
    totalRequests += item.count
    totalErrors400 += item.status400
    totalErrors500 += item.status500
    totalDuration += item.duration * item.count
    if (item.lastStatus) lastStatus = item.lastStatus
  }

  const avgRequestTime = totalRequests > 0 ? Math.round(totalDuration / totalRequests) : 0
  const hasErrors = totalErrors400 > 0 || totalErrors500 > 0

  const connector: Connector = {
    nodeIds,
    name: items[0]!.nodeId,
    application: items[0]!.applicationId,
    topologyIds: [...new Set(items.map(i => i.topologyId))],
    avgRequestTime,
    requests: totalRequests,
    errors400: totalErrors400,
    errors500: totalErrors500,
    lastRequestStatus: lastStatus,
    status: hasErrors ? 'errors' : 'ok',
  }
  return {
    connector,
    errors400: totalErrors400,
    errors500: totalErrors500,
    totalRequests,
    lastRequestStatus: lastStatus,
    lastRequestTime: lastRecord?.duration || 0,
    avgRequestTime,
    errorRecords: [],
  }
}

/** Quick filter for connector / process audit error records (HTTP response code family). */
export type ConnectorErrorRecordsCodeFilter = 'all' | '400' | '500'

export function metricStatusForConnectorErrorCodeFilter(
  codeFilter: ConnectorErrorRecordsCodeFilter,
): 'FAILED' | 'FAILED_400' | 'FAILED_500' {
  if (codeFilter === '400') return 'FAILED_400'
  if (codeFilter === '500') return 'FAILED_500'
  return 'FAILED'
}

/**
 * Fetch connector error records with pagination.
 * Filters by nodeId array so all nodes with the same name are included.
 */
export async function fetchConnectorErrorRecords(
  nodeIds: string[],
  timeFilter: TimeFilter,
  page: number = 1,
  limit: number = 10,
  sortField: string = 'created',
  sortDirection: string = 'desc',
  codeFilter: ConnectorErrorRecordsCodeFilter = 'all',
): Promise<PaginatedResponse<ConnectorErrorRecord>> {
  const dateRange = convertTimeFilterToDateTimeRange(timeFilter)
  const dateFrom = formatDateTimeForApi(dateRange.from) || ''

  const filterObj: ConnectorApiFilter = {
    search: null,
    filter: [
      [{ column: 'created', operator: 'GTE', value: [dateFrom] }],
      [{ column: 'nodeId', operator: 'EQ', value: nodeIds }],
      [
        {
          column: 'status',
          operator: 'EQ',
          value: [metricStatusForConnectorErrorCodeFilter(codeFilter)],
        },
      ],
    ],
    sorter: [{ column: sortField, direction: sortDirection.toUpperCase() }],
    paging: {
      itemsPerPage: limit,
      page: page
    }
  }

  // Call API
  const response = await api.get<ConnectorErrorApiResponse>('/api/metrics/connectors', {
    params: {
      filter: JSON.stringify(filterObj)
    }
  })

  // Map API items to error records
  const errorRecords = response.data.items.map(item =>
    mapErrorApiItemToRecord(item)
  )

  return {
    data: errorRecords,
    meta: {
      totalItems: response.data.paging.total,
      totalPages: response.data.paging.lastPage,
      currentPage: response.data.paging.page,
      itemsPerPage: response.data.paging.itemsPerPage,
    },
  }
}

/**
 * Fetch connector chart data for error visualization.
 * Filters by nodeId array so all nodes with the same name are included.
 */
export async function fetchConnectorChartData(
  nodeIds: string[],
  timeFilter: TimeFilter,
  buckets: number,
): Promise<{ categories: number[]; errors400: number[]; errors500: number[] }> {
  const dateRange = convertTimeFilterToDateTimeRange(timeFilter)
  const dateFrom = formatDateTimeForApi(dateRange.from) || ''
  const dateTo = formatDateTimeForApi(dateRange.to) || ''

  const filterObj: ConnectorApiFilter = {
    search: null,
    filter: [
      [{ column: 'created', operator: 'BETWEEN', value: [dateFrom, dateTo] }],
      [{ column: 'nodeId', operator: 'EQ', value: nodeIds }]
    ],
    sorter: [{ column: 'created', direction: 'ASC' }],
    paging: {
      itemsPerPage: 9999,
      page: 1
    }
  }

  // Call API
  const response = await api.get<ConnectorGraphApiResponse>('/api/metrics/connectors/graph', {
    params: {
      filter: JSON.stringify(filterObj),
      buckets
    }
  })

  // Map to chart data format
  return mapGraphApiToChartData(response.data.items)
}

