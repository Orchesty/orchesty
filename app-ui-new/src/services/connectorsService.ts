import type { PaginatedResponse, QueryParams } from '@/types/api'
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
  ConnectorErrorApiResponse
} from '@/types/connectors'
import type { TimeFilter } from '@/types/dashboard'
import api from '@/services/api'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApi } from '@/utils/timeRangeConverter'

/**
 * Map API connector item to UI Connector model
 */
function mapApiItemToConnector(apiItem: ConnectorApiItem): Connector {
  const hasErrors = apiItem.status400 > 0 || apiItem.status500 > 0
  return {
    id: apiItem.nodeId,
    application: apiItem.applicationId,
    avgRequestTime: apiItem.duration,
    requests: apiItem.count,
    errors400: apiItem.status400,
    errors500: apiItem.status500,
    lastRequestStatus: apiItem.lastStatus,
    status: hasErrors ? 'errors' : 'ok'
  }
}

/**
 * Map UI sort field to API column name
 */
function mapSortFieldToApiColumn(field: string): string {
  const fieldMap: Record<string, string> = {
    'avgRequestTime': 'duration',
    'requests': 'count',
    'lastRequestStatus': 'lastStatus',
    'application': 'applicationId',
    'name': 'nodeId',
    'errors400': 'status400',
    'errors500': 'status500'
  }
  return fieldMap[field] || field
}

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
 * Fetch connectors with filters, sorting, and pagination
 *
 * @param params - Query parameters for filtering, sorting, and pagination
 * @returns Paginated response with connectors data
 */
export async function fetchConnectors(
  params: ConnectorQueryParams,
): Promise<PaginatedResponse<Connector>> {
  // Build API filter object
  const filterObj: ConnectorApiFilter = {
    search: null,
    filter: [],
    sorter: [],
    paging: {
      itemsPerPage: params.limit || 10,
      page: params.page || 1
    }
  }

  // Add status filter (ok = COMPLETED, errors = FAILED)
  if (params.status && params.status !== 'all') {
    if (params.status === 'ok') {
      filterObj.filter.push([{ column: 'status', operator: 'EQ', value: ['COMPLETED'] }])
    } else if (params.status === 'errors') {
      filterObj.filter.push([{ column: 'status', operator: 'EQ', value: ['FAILED'] }])
    }
  }

  // Add node filter
  if (params.node) {
    const nodeValues = Array.isArray(params.node) ? params.node : [params.node]
    filterObj.filter.push([{ column: 'nodeId', operator: 'EQ', value: nodeValues }])
  }

  // Add application filter
  if (params.application) {
    filterObj.filter.push([{ column: 'applicationId', operator: 'EQ', value: [params.application] }])
  }

  // Add date range filter
  if (params.dateFrom && params.dateTo) {
    filterObj.filter.push([{
      column: 'created',
      operator: 'BETWEEN',
      value: [params.dateFrom, params.dateTo]
    }])
  }

  // Add sorting
  if (params.sort && params.order) {
    const apiColumn = mapSortFieldToApiColumn(params.sort)
    filterObj.sorter.push({
      column: apiColumn,
      direction: params.order.toUpperCase()
    })
  }

  // Make API call
  const response = await api.get<ConnectorApiResponse>('/api/metrics/connectors/overview', {
    params: {
      filter: JSON.stringify(filterObj)
    }
  })

  // Map API items to UI model
  const connectors = response.data.items.map(mapApiItemToConnector)

  return {
    data: connectors,
    meta: {
      totalItems: response.data.paging.total,
      totalPages: response.data.paging.lastPage,
      currentPage: response.data.paging.page,
      itemsPerPage: response.data.paging.itemsPerPage,
    },
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
  const dateTo = formatDateTimeForApi(dateRange.to) || ''

  const filterObj: ConnectorApiFilter = {
    search: null,
    filter: [
      [{ column: 'created', operator: 'BETWEEN', value: [dateFrom, dateTo] }],
      [{ column: 'nodeId', operator: 'EQ', value: nodeIds }]
    ],
    sorter: [],
    paging: {
      itemsPerPage: 1,
      page: 1
    }
  }

  const response = await api.get<ConnectorApiResponse>('/api/metrics/connectors/overview', {
    params: {
      filter: JSON.stringify(filterObj)
    }
  })

  const items = response.data.items

  if (items.length === 0) {
    return {
      connector: {
        id: nodeIds[0] || '',
        application: '',
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
      errorRecords: [],
    }
  }

  const connector = mapApiItemToConnector(items[0])
  return {
    connector,
    errors400: items[0].status400,
    errors500: items[0].status500,
    totalRequests: items[0].count,
    lastRequestStatus: items[0].lastStatus,
    errorRecords: [],
  }
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
  sortDirection: string = 'desc'
): Promise<PaginatedResponse<ConnectorErrorRecord>> {
  const dateRange = convertTimeFilterToDateTimeRange(timeFilter)
  const dateFrom = formatDateTimeForApi(dateRange.from) || ''
  const dateTo = formatDateTimeForApi(dateRange.to) || ''

  const filterObj: ConnectorApiFilter = {
    search: null,
    filter: [
      [{ column: 'created', operator: 'BETWEEN', value: [dateFrom, dateTo] }],
      [{ column: 'nodeId', operator: 'EQ', value: nodeIds }],
      [{ column: 'status', operator: 'EQ', value: ['FAILED'] }]
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

