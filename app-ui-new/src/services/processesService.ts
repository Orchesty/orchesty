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
import api from '@/services/api'

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
    errorMessage: item.headers['result-message'] as string || 'No error message'
  }))

  return {
    items,
    total: response.data.paging.total
  }
}
