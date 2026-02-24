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
import { convertTimeFilterToDateTimeRange } from '@/utils/timeRangeConverter'
import { useDateFormat } from '@/composables/useDateFormat'

const { formatChartDate } = useDateFormat()

/**
 * Map API connector item to UI Connector model
 */
function mapApiItemToConnector(apiItem: ConnectorApiItem): Connector {
  // Determine status based on error counts
  const hasErrors = apiItem.status400 > 0 || apiItem.status500 > 0
  return {
    id: apiItem.nodeId,
    name: apiItem.nodeId, // Will be replaced by node name in component
    application: apiItem.applicationId, // Will be replaced by app name in component
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
  categories: string[]
  errors400: number[]
  errors500: number[]
} {
  return {
    categories: items.map(item => formatChartDate(item.created)),
    errors400: items.map(item => item.status400),
    errors500: items.map(item => item.status500)
  }
}

/**
 * Map error API item to ConnectorErrorRecord
 */
function mapErrorApiItemToRecord(
  apiItem: ConnectorErrorApiItem,
  getTopologyName: (id: string) => string
): ConnectorErrorRecord {
  return {
    timestamp: apiItem.created,
    topology: getTopologyName(apiItem.topologyId),
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
    filterObj.filter.push([{ column: 'nodeId', operator: 'EQ', value: [params.node] }])
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
 * Fetch connector detail with error statistics
 *
 * @param connectorId - The connector ID
 * @param timeFilter - Time filter for data aggregation
 * @returns Connector detail with error stats
 */
export async function fetchConnectorDetail(
  connectorId: string,
  timeFilter: TimeFilter,
): Promise<ConnectorDetail> {
  // Convert time filter to date range
  const dateRange = convertTimeFilterToDateTimeRange(timeFilter)

  // Build API filter object
  const filterObj: ConnectorApiFilter = {
    search: null,
    filter: [
      [{ column: 'created', operator: 'BETWEEN', value: [dateRange.from, dateRange.to] }],
      [{ column: 'nodeId', operator: 'EQ', value: [connectorId] }]
    ],
    sorter: [],
    paging: {
      itemsPerPage: 1,
      page: 1
    }
  }

  // Call API
  const response = await api.get<ConnectorApiResponse>('/api/metrics/connectors/overview', {
    params: {
      filter: JSON.stringify(filterObj)
    }
  })

  // Get first item (should be only one for specific nodeId)
  const apiItem = response.data.items[0]

  if (!apiItem) {
    // If no data found for this time range, return a connector with zero stats
    return {
      connector: {
        id: connectorId,
        name: connectorId,
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

  // Map to connector model
  const connector = mapApiItemToConnector(apiItem)

  return {
    connector,
    errors400: apiItem.status400,
    errors500: apiItem.status500,
    totalRequests: apiItem.count,
    lastRequestStatus: apiItem.lastStatus,
    errorRecords: [], // Will be fetched separately
  }
}

/**
 * Fetch connector error records with pagination
 *
 * @param connectorId - The connector ID
 * @param timeFilter - Time filter for records
 * @param page - Page number
 * @param limit - Items per page
 * @param getTopologyName - Function to map topology ID to name
 * @returns Paginated error records
 */
export async function fetchConnectorErrorRecords(
  connectorId: string,
  timeFilter: TimeFilter,
  page: number = 1,
  limit: number = 10,
  getTopologyName: (id: string) => string = (id) => id,
  sortField: string = 'created',
  sortDirection: string = 'desc'
): Promise<PaginatedResponse<ConnectorErrorRecord>> {
  // Convert time filter to date range
  const dateRange = convertTimeFilterToDateTimeRange(timeFilter)

  // Build API filter object
  const filterObj: ConnectorApiFilter = {
    search: null,
    filter: [
      [{ column: 'created', operator: 'BETWEEN', value: [dateRange.from, dateRange.to] }],
      [{ column: 'nodeId', operator: 'EQ', value: [connectorId] }],
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
    mapErrorApiItemToRecord(item, getTopologyName)
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
 * Fetch connector chart data for error visualization
 *
 * @param connectorId - The connector ID
 * @param timeFilter - Time filter for chart data
 * @returns Chart data with categories and error counts
 */
export async function fetchConnectorChartData(
  connectorId: string,
  timeFilter: TimeFilter,
): Promise<{ categories: string[]; errors400: number[]; errors500: number[] }> {
  // Convert time filter to date range
  const dateRange = convertTimeFilterToDateTimeRange(timeFilter)

  // Build API filter object
  const filterObj: ConnectorApiFilter = {
    search: null,
    filter: [
      [{ column: 'created', operator: 'BETWEEN', value: [dateRange.from, dateRange.to] }],
      [{ column: 'nodeId', operator: 'EQ', value: [connectorId] }]
    ],
    sorter: [{ column: 'created', direction: 'ASC' }],
    paging: {
      itemsPerPage: 9999, // Get all data points for chart
      page: 1
    }
  }

  // Call API
  const response = await api.get<ConnectorGraphApiResponse>('/api/metrics/connectors/graph', {
    params: {
      filter: JSON.stringify(filterObj)
    }
  })

  // Map to chart data format
  return mapGraphApiToChartData(response.data.items)
}

