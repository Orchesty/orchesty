import type { LogEntry, LogQueryParams, LogApiFilter, LogApiResponse } from '@/types/logs'
import api from '@/services/api'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApiFilter } from '@/utils/timeRangeConverter'

interface LogsResponse {
  data: LogEntry[]
  pagination: {
    total: number
    page: number
    perPage: number
    totalPages: number
  }
}

/**
 * Map UI sort field to API column name
 */
function mapSortFieldToApiColumn(field: string): string {
  switch (field) {
    case 'timestamp': return 'created'
    case 'topology': return 'topologyId'
    case 'node': return 'nodeId'
    default: return field
  }
}

/**
 * Fetch logs with filtering, sorting, and pagination
 */
export async function fetchLogs(params: LogQueryParams = {}): Promise<LogsResponse> {
  const sortColumn = mapSortFieldToApiColumn(params.sortBy || 'created')

  // Build filter object
  const filterObj: LogApiFilter = {
    search: params.search || null,
    filter: [],
    sorter: [
      {
        column: sortColumn,
        direction: (params.sortOrder || 'desc').toUpperCase()
      }
    ],
    paging: {
      itemsPerPage: params.perPage || 10,
      page: params.page || 1
    }
  }

  // Add correlation ID filter
  if (params.correlationId) {
    filterObj.filter.push([
      {
        column: 'correlationId',
        operator: 'EQ',
        value: [params.correlationId]
      }
    ])
  }

  // Add severity filter
  if (params.severity) {
    filterObj.filter.push([
      {
        column: 'severity',
        operator: 'EQ',
        value: [params.severity]
      }
    ])
  }

  // Add topology filter
  if (params.topology) {
    filterObj.filter.push([
      {
        column: 'topologyId',
        operator: 'EQ',
        value: [params.topology]
      }
    ])
  }

  // Add node filter
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

  // Add time range filter
  if (params.timeRange) {
    const dateRange = convertTimeFilterToDateTimeRange(params.timeRange)
    const fromISO = formatDateTimeForApiFilter(dateRange.from)
    const toISO = formatDateTimeForApiFilter(dateRange.to)

    filterObj.filter.push([
      {
        column: 'created',
        operator: 'BETWEEN',
        value: [fromISO, toISO]
      }
    ])
  }

  // Make API request
  const response = await api.get<LogApiResponse>(
    `/api/logs?filter=${encodeURIComponent(JSON.stringify(filterObj))}`
  )

  // Map API items to LogEntry (raw IDs; names resolved reactively in template)
  const mappedItems: LogEntry[] = response.data.items.map(item => ({
    id: item.id,
    timestamp: item.created,
    topology: item.topologyId,
    topologyId: item.topologyId,
    node: item.nodeId,
    nodeId: item.nodeId,
    correlationId: item.correlationId,
    severity: item.severity as LogEntry['severity'],
    message: item.message,
  }))

  return {
    data: mappedItems,
    pagination: {
      total: response.data.paging.total,
      page: response.data.paging.page,
      perPage: response.data.paging.itemsPerPage,
      totalPages: response.data.paging.lastPage,
    },
  }
}

