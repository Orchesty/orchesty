import type {
  TrashItem,
  TrashQueryParams,
  TrashApiFilter,
  TrashApiResponse,
  TrashItemApi,
  TopologyNodeMappings
} from '@/types/trash'
import api from './api'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApiFilter } from '@/utils/timeRangeConverter'

interface PaginatedResponse<T> {
  data: T[]
  pagination: {
    page: number
    perPage: number
    total: number
    totalPages: number
  }
}

/**
 * Map API response item to TrashItem
 */
function mapApiItemToTrashItem(apiItem: TrashItemApi): TrashItem {
  // Parse body JSON string, fallback to empty object on error
  let parsedBody: Record<string, unknown> = {}
  try {
    parsedBody = JSON.parse(apiItem.body)
  } catch (error) {
    console.error('Failed to parse body JSON:', error)
  }

  return {
    id: apiItem.id,
    nodeId: apiItem.nodeId,
    topologyId: apiItem.topologyId,
    correlationId: apiItem.correlationId,
    timestamp: apiItem.created,
    // Use IDs as display values for now
    topology: apiItem.topologyId,
    node: apiItem.nodeId,
    headers: apiItem.headers || {},
    body: parsedBody,
  }
}

/**
 * Fetch trash items with filtering, sorting, and pagination
 */
export async function fetchTrashItems(
  params: TrashQueryParams
): Promise<PaginatedResponse<TrashItem>> {
  // Map component field names to API field names
  const sortColumn = params.sortBy === 'timestamp' ? 'created' : (params.sortBy || 'created')

  // Build the complex filter structure
  const filterObj: TrashApiFilter = {
    search: params.search || null,
    filter: [
      [
        {
          column: 'type',
          operator: 'EQ',
          value: ['trash']
        }
      ]
    ],
    sorter: [
      {
        column: sortColumn,
        direction: params.sortOrder === 'desc' ? 'DESC' : 'ASC'
      }
    ],
    paging: {
      itemsPerPage: params.perPage || 10,
      page: params.page || 1
    }
  }

  // Each additional filter goes in its own group for AND logic
  if (params.correlationId) {
    filterObj.filter.push([
      {
        column: 'correlationId',
        operator: 'EQ',
        value: [params.correlationId]
      }
    ])
  }

  if (params.node) {
    const nodeValues = Array.isArray(params.node) ? params.node : [params.node]
    filterObj.filter.push([
      {
        column: 'nodeId',
        operator: 'EQ',
        value: nodeValues
      }
    ])
  }

  if (params.topology) {
    filterObj.filter.push([
      {
        column: 'topologyId',
        operator: 'EQ',
        value: [params.topology]
      }
    ])
  }

  // Handle time range filter
  if (params.dateFrom && params.dateTo) {
    filterObj.filter.push([
      { column: 'created', operator: 'BETWEEN', value: [params.dateFrom, params.dateTo] }
    ])
  } else if (params.dateFrom) {
    filterObj.filter.push([
      { column: 'created', operator: 'GTE', value: [params.dateFrom] }
    ])
  } else if (params.timeRange) {
    const dateRange = convertTimeFilterToDateTimeRange(params.timeRange)
    const fromISO = formatDateTimeForApiFilter(dateRange.from)

    filterObj.filter.push([
      { column: 'created', operator: 'GTE', value: [fromISO] }
    ])
  }

  // Encode filter as URL parameter
  const encodedFilter = encodeURIComponent(JSON.stringify(filterObj))

  // Make API request
  const response = await api.get<TrashApiResponse>(`/api/user-tasks?filter=${encodedFilter}`)

  // Map API response to component format
  const mappedItems = response.data.items.map(mapApiItemToTrashItem)

  return {
    data: mappedItems,
    pagination: {
      page: response.data.paging.page,
      perPage: response.data.paging.itemsPerPage,
      total: response.data.paging.total,
      totalPages: response.data.paging.lastPage,
    },
  }
}

/**
 * Fetch topology, node, and application name mappings (all=1, includes disabled/inactive)
 * Used for ID-to-name resolution throughout the app.
 */
export async function fetchTopologyNodeMappings(): Promise<TopologyNodeMappings> {
  const response = await api.get<TopologyNodeMappings>('/api/applications/topologies/nodes', {
    params: { all: 1 },
  })
  return response.data
}

/**
 * Fetch topology, node, and application name mappings (only active/relevant)
 * Used for populating dropdown filters.
 */
export async function fetchFilteredMappings(): Promise<TopologyNodeMappings> {
  const response = await api.get<TopologyNodeMappings>('/api/applications/topologies/nodes')
  return response.data
}

/**
 * Approve a trash item
 */
export async function approveTrashItem(id: string): Promise<void> {
  await api.post(`/api/user-task/${id}/accept`)
}

/**
 * Reject a trash item
 */
export async function rejectTrashItem(id: string): Promise<void> {
  await api.post(`/api/user-task/${id}/reject`)
}

/**
 * Update a trash item
 */
export async function updateTrashItem(
  id: string,
  data: { headers: Record<string, unknown>; body: Record<string, unknown> }
): Promise<{ headers: Record<string, unknown>; body: Record<string, unknown> }> {
  // Convert body object to JSON string as API expects
  const payload = {
    body: JSON.stringify(data.body),
    headers: data.headers
  }
  const response = await api.put<{ message: { body: string; headers: Record<string, unknown> } }>(
    `/api/user-task/${id}`,
    payload
  )

  // Parse the body JSON string from API response
  let parsedBody: Record<string, unknown> = {}
  try {
    parsedBody = JSON.parse(response.data.message.body)
  } catch (error) {
    console.error('Failed to parse body JSON:', error)
  }

  return {
    headers: response.data.message.headers,
    body: parsedBody
  }
}

/**
 * Bulk approve trash items
 */
export async function bulkApprove(ids: string[]): Promise<void> {
  await api.post('/api/user-task/accept', { ids })
}

/**
 * Bulk reject trash items
 */
export async function bulkReject(ids: string[]): Promise<void> {
  await api.post('/api/user-task/reject', { ids })
}

export async function approveAllTrashItems(
  topologyId: string, nodeId: string, correlationId: string,
): Promise<void> {
  await api.post('/api/user-task/accept', {
    type: 'trash', topologyId, nodeId, correlationId,
  })
}

export async function rejectAllTrashItems(
  topologyId: string, nodeId: string, correlationId: string,
): Promise<void> {
  await api.post('/api/user-task/reject', {
    type: 'trash', topologyId, nodeId, correlationId,
  })
}

export interface TrashFilterParams {
  topologyId?: string
  nodeId?: string | string[]
  correlationId?: string
  resultMessage?: string
  search?: string
  dateFrom?: string
  dateTo?: string
}

function buildFilterBody(params: TrashFilterParams): Record<string, unknown> {
  const body: Record<string, unknown> = { type: 'trash' }
  if (params.topologyId) body.topologyId = params.topologyId
  if (params.nodeId) body.nodeId = params.nodeId
  if (params.correlationId) body.correlationId = params.correlationId
  if (params.resultMessage !== undefined) body.resultMessage = params.resultMessage
  if (params.search) body.search = params.search
  if (params.dateFrom) body.dateFrom = params.dateFrom
  if (params.dateTo) body.dateTo = params.dateTo
  return body
}

export async function approveByFilter(params: TrashFilterParams): Promise<void> {
  await api.post('/api/user-task/accept', buildFilterBody(params))
}

export async function rejectByFilter(params: TrashFilterParams): Promise<void> {
  await api.post('/api/user-task/reject', buildFilterBody(params))
}

