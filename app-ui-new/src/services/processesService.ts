import type { PaginatedResponse } from '@/types/api'
import type {
  Process,
  ProcessQueryParams,
  ProcessApiItem,
  ProcessApiResponse,
  ProcessApiFilter,
  ProcessConnector,
  ProcessTrashItem,
  ProcessAuditConnectorApiResponse,
  ProcessAuditTrashApiResponse,
  ProcessAuditApiFilter
} from '@/types/processes'
import type { ConnectorErrorRecord, ConnectorErrorApiResponse } from '@/types/connectors'
import type { TimeFilter } from '@/types/dashboard'
import api from '@/services/api'
import {
  metricStatusForConnectorErrorCodeFilter,
  type ConnectorErrorRecordsCodeFilter,
} from '@/services/connectorsService'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApi } from '@/utils/timeRangeConverter'

/**
 * Map API process item to UI Process model
 */
function mapApiItemToProcess(apiItem: ProcessApiItem): Process {
  return {
    id: apiItem.id,
    topology: apiItem.topologyId, // Will be mapped to name in component
    topologyId: apiItem.topologyId,
    startTime: apiItem.created,
    duration: apiItem.duration,
    status: mapApiStatusToUiStatus(apiItem.status),
    errorMessage: apiItem.messages.length > 0 ? apiItem.messages[0] : undefined
  }
}

/**
 * Map API status to UI status
 */
function mapApiStatusToUiStatus(apiStatus: string): 'running' | 'completed' | 'failed' {
  if (apiStatus === 'COMPLETED') return 'completed'
  if (apiStatus === 'RUNNING') return 'running'
  if (apiStatus === 'FAILED') return 'failed'
  return 'completed' // default
}

/**
 * Map UI status to API status
 */
function mapUiStatusToApiStatus(uiStatus: string): string {
  if (uiStatus === 'completed') return 'COMPLETED'
  if (uiStatus === 'running') return 'RUNNING'
  if (uiStatus === 'failed') return 'FAILED'
  return 'COMPLETED' // default
}

/**
 * Map UI sort field to API column name
 */
function mapSortFieldToApiColumn(field: string): string {
  const fieldMap: Record<string, string> = {
    'topology': 'topologyId',
    'startTime': 'created',
    'duration': 'duration',
    'status': 'status'
  }
  return fieldMap[field] || field
}

/**
 * Fetch processes with filters, sorting, and pagination
 *
 * @param params - Query parameters for filtering, sorting, and pagination
 * @returns Paginated response with processes data
 */
export async function fetchProcesses(
  params: ProcessQueryParams,
): Promise<PaginatedResponse<Process>> {
  // Build API filter object
  const filterObj: ProcessApiFilter = {
    search: null,
    filter: [],
    sorter: [],
    paging: {
      itemsPerPage: params.limit || 10,
      page: params.page || 1
    }
  }

  // Add status filter
  if (params.status && params.status !== 'all') {
    const apiStatus = mapUiStatusToApiStatus(params.status)
    filterObj.filter.push([{ column: 'status', operator: 'EQ', value: [apiStatus] }])
  }

  // Add topology filter (supports multiple IDs for version grouping)
  if (params.topologyIds && params.topologyIds.length > 0) {
    filterObj.filter.push([{ column: 'topologyId', operator: 'IN', value: params.topologyIds }])
  } else if (params.topology) {
    filterObj.filter.push([{ column: 'topologyId', operator: 'EQ', value: [params.topology] }])
  }

  // Add date range filter
  if (params.dateFrom) {
    filterObj.filter.push([params.dateTo
      ? { column: 'created', operator: 'BETWEEN', value: [params.dateFrom, params.dateTo] }
      : { column: 'created', operator: 'GTE', value: [params.dateFrom] }
    ])
  }

  // Add source filter
  if (params.source) {
    filterObj.filter.push([{ column: 'source', operator: 'EQ', value: [params.source] }])
  }

  // Add sorting
  if (params.sort && params.order) {
    const apiColumn = mapSortFieldToApiColumn(params.sort)
    filterObj.sorter.push({
      column: apiColumn,
      direction: params.order.toUpperCase()
    })
  } else {
    // Default sort: created DESC
    filterObj.sorter.push({
      column: 'created',
      direction: 'DESC'
    })
  }

  // Make API call
  const response = await api.get<ProcessApiResponse>('/api/processes', {
    params: {
      filter: JSON.stringify(filterObj)
    }
  })

  // Map API items to UI model
  const processes = response.data.items.map(mapApiItemToProcess)

  return {
    data: processes,
    meta: {
      totalItems: response.data.paging.total,
      totalPages: response.data.paging.lastPage,
      currentPage: response.data.paging.page,
      itemsPerPage: response.data.paging.itemsPerPage,
    },
  }
}

/**
 * Fetch the most recent process for a topology (sorted by created DESC, limit 1).
 * Returns null if no processes exist.
 */
export async function fetchLatestProcess(topologyId: string): Promise<Process | null> {
  const result = await fetchProcesses({
    topology: topologyId,
    sort: 'startTime',
    order: 'desc',
    limit: 1,
    page: 1,
    source: 'ui',
  })
  return result.data[0] ?? null
}

/**
 * Fetch connectors for a specific process (by correlationId)
 *
 * @param correlationId - The correlation ID of the process
 * @returns Array of process connectors
 */
export async function fetchProcessAuditConnectors(
  correlationId: string,
  sortField: string = 'count',
  sortDirection: string = 'desc'
): Promise<ProcessConnector[]> {
  const filter: ProcessAuditApiFilter = {
    search: null,
    filter: [
      [
        {
          column: 'correlationId',
          operator: 'EQ',
          value: [correlationId]
        }
      ]
    ],
    sorter: [{ column: sortField, direction: sortDirection.toUpperCase() }],
    paging: {
      itemsPerPage: 100, // Get all connectors for this process
      page: 1
    }
  }

  const response = await api.get<ProcessAuditConnectorApiResponse>(
    `/api/metrics/connectors/overview?filter=${encodeURIComponent(JSON.stringify(filter))}`
  )

  // Map API items to UI model
  return response.data.items.map(item => ({
    connector: item.nodeId, // Will be mapped to name in component
    application: item.applicationId || 'N/A',
    called: item.count,
    errors400: item.status400,
    errors500: item.status500
  }))
}

/**
 * Fetch trash items for a specific process (by correlationId)
 *
 * @param correlationId - The correlation ID of the process
 * @returns Object with trash items array and total count
 */
export async function fetchProcessAuditTrash(
  correlationId: string
): Promise<{ items: ProcessTrashItem[]; total: number }> {
  const filter: ProcessAuditApiFilter = {
    search: null,
    filter: [
      [
        {
          column: 'type',
          operator: 'EQ',
          value: ['trash']
        }
      ],
      [
        {
          column: 'correlationId',
          operator: 'EQ',
          value: [correlationId]
        }
      ]
    ],
    sorter: [{ column: 'created', direction: 'DESC' }],
    paging: {
      itemsPerPage: 10, // Show first 10 items in the table
      page: 1
    }
  }

  const response = await api.get<ProcessAuditTrashApiResponse>(
    `/api/user-tasks?filter=${encodeURIComponent(JSON.stringify(filter))}`
  )

  // Map API items to UI model
  const items = response.data.items.map(item => ({
    whereItFailed: item.nodeId, // Will be mapped to node name in component
    errorMessage: item.headers['result-message'] as string || 'No error message',
    topologyId: item.topologyId,
    correlationId: item.correlationId,
  }))

  return {
    items,
    total: response.data.paging.total
  }
}

/**
 * Fetch trash (failed messages) for a connector scope: filter by nodeId(s) and optional time range.
 */
export async function fetchConnectorTrash(
  nodeIds: string[],
  timeFilter: TimeFilter,
): Promise<{ items: ProcessTrashItem[]; total: number }> {
  const dateRange = convertTimeFilterToDateTimeRange(timeFilter)
  const dateFrom = formatDateTimeForApi(dateRange.from) || ''

  const filter: ProcessAuditApiFilter = {
    search: null,
    filter: [
      [
        {
          column: 'type',
          operator: 'EQ',
          value: ['trash'],
        },
      ],
      [
        {
          column: 'nodeId',
          operator: 'EQ',
          value: nodeIds,
        },
      ],
      [{ column: 'created', operator: 'GTE', value: [dateFrom] }],
    ],
    sorter: [{ column: 'created', direction: 'DESC' }],
    paging: {
      itemsPerPage: 10,
      page: 1,
    },
  }

  const response = await api.get<ProcessAuditTrashApiResponse>(
    `/api/user-tasks?filter=${encodeURIComponent(JSON.stringify(filter))}`,
  )

  const items = response.data.items.map((item) => ({
    whereItFailed: item.nodeId,
    errorMessage: (item.headers['result-message'] as string) || 'No error message',
    topologyId: item.topologyId,
    correlationId: item.correlationId,
  }))

  return {
    items,
    total: response.data.paging.total,
  }
}

/**
 * Fetch connector error records for a specific process (by correlationId)
 * with pagination and sorting. Uses the same endpoint as connector error records
 * but filters by correlationId instead of nodeId.
 */
export async function fetchProcessAuditErrorRecords(
  correlationId: string,
  page: number = 1,
  limit: number = 10,
  sortField: string = 'created',
  sortDirection: string = 'desc',
  codeFilter: ConnectorErrorRecordsCodeFilter = 'all',
): Promise<PaginatedResponse<ConnectorErrorRecord>> {
  const filterObj: ProcessAuditApiFilter = {
    search: null,
    filter: [
      [{ column: 'correlationId', operator: 'EQ', value: [correlationId] }],
      [
        {
          column: 'status',
          operator: 'EQ',
          value: [metricStatusForConnectorErrorCodeFilter(codeFilter)],
        },
      ],
    ],
    sorter: [{ column: sortField, direction: sortDirection.toUpperCase() }],
    paging: { itemsPerPage: limit, page }
  }

  const response = await api.get<ConnectorErrorApiResponse>('/api/metrics/connectors', {
    params: { filter: JSON.stringify(filterObj) }
  })

  const records = response.data.items.map(item => ({
    id: item.id,
    timestamp: item.created,
    topologyId: item.topologyId,
    topology: item.topologyId,
    nodeId: item.nodeId,
    applicationId: item.applicationId,
    correlationId: item.correlationId || '',
    userId: item.userId || '',
    duration: item.duration || 0,
    code: item.status,
    message: item.message || ''
  }))

  return {
    data: records,
    meta: {
      totalItems: response.data.paging.total,
      totalPages: response.data.paging.lastPage,
      currentPage: response.data.paging.page,
      itemsPerPage: response.data.paging.itemsPerPage,
    },
  }
}
