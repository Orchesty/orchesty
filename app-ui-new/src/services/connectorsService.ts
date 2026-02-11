import type { PaginatedResponse, QueryParams } from '@/types/api'
import type { Connector, ConnectorQueryParams, ConnectorDetail, ConnectorErrorRecord } from '@/types/connectors'
import type { TimeFilter } from '@/types/dashboard'
import connectorsDataJson from '@/assets/mock-data/connectors-data.json'
import connectorDetailDataJson from '@/assets/mock-data/connector-detail-data.json'

/**
 * Build URL search params from query object
 * NOTE: Currently not used with mock data, will be used when connecting to real API
 */
// eslint-disable-next-line @typescript-eslint/no-unused-vars
function buildQueryParams(params: QueryParams): URLSearchParams {
  const searchParams = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      searchParams.append(key, String(value))
    }
  })

  return searchParams
}

/**
 * Fetch connectors with filters, sorting, and pagination
 * Currently returns filtered mock data, will be replaced with API call
 * 
 * @param params - Query parameters for filtering, sorting, and pagination
 * @returns Paginated response with connectors data
 */
export async function fetchConnectors(
  params: ConnectorQueryParams,
): Promise<PaginatedResponse<Connector>> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 300))

  // FOR DEVELOPMENT: Filter mock data
  // In production: return axios.get('/api/connectors', { params: buildQueryParams(params) })

  let filtered = [...(connectorsDataJson.data as Connector[])]

  // Apply status filter
  if (params.status && params.status !== 'all') {
    filtered = filtered.filter((c) => c.status === params.status)
  }

  // Apply application filter
  if (params.application) {
    filtered = filtered.filter(
      (c) => c.application.toLowerCase() === params.application?.toLowerCase(),
    )
  }

  // Apply search
  if (params.search) {
    const search = params.search.toLowerCase()
    filtered = filtered.filter(
      (c) =>
        c.name.toLowerCase().includes(search) ||
        c.application.toLowerCase().includes(search),
    )
  }

  // Apply datetime range filter
  // NOTE: In production, backend will aggregate data (requests, errors, etc.) based on this datetime range
  // For mock data, we just log the range without actual filtering
  if (params.dateFrom || params.dateTo) {
    console.log('Connectors datetime filter:', {
      from: params.dateFrom,
      to: params.dateTo,
    })
    // TODO: Backend will filter/aggregate connector statistics for this datetime range
  }

  // Apply sorting
  if (params.sort && params.order) {
    filtered.sort((a, b) => {
      const aVal = a[params.sort as keyof Connector] as number | string
      const bVal = b[params.sort as keyof Connector] as number | string
      const comparison = aVal > bVal ? 1 : -1
      return params.order === 'asc' ? comparison : -comparison
    })
  }

  // Apply pagination
  const page = params.page || 1
  const limit = params.limit || 10
  const startIndex = (page - 1) * limit
  const endIndex = startIndex + limit

  const paginated = filtered.slice(startIndex, endIndex)

  return {
    data: paginated,
    meta: {
      totalItems: filtered.length,
      totalPages: Math.ceil(filtered.length / limit),
      currentPage: page,
      itemsPerPage: limit,
    },
  }
}

/**
 * Fetch connector detail with error statistics
 * Currently returns mock data, will be replaced with API call
 * 
 * @param connectorId - The connector ID
 * @param timeFilter - Time filter for data aggregation
 * @returns Connector detail with error stats
 */
export async function fetchConnectorDetail(
  connectorId: string,
  timeFilter: TimeFilter, // eslint-disable-line @typescript-eslint/no-unused-vars
): Promise<ConnectorDetail> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 300))

  // FOR DEVELOPMENT: Return mock data
  // In production: return axios.get(`/api/connectors/${connectorId}/detail`, { params: { timeFilter } })
  // NOTE: timeFilter will be used when connecting to real API

  // Find the connector
  const connector = (connectorsDataJson.data as Connector[]).find((c) => c.id === connectorId)
  
  if (!connector) {
    throw new Error(`Connector not found: ${connectorId}`)
  }

  // Get mock detail data
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const detailData = (connectorDetailDataJson as any)[connectorId] || (connectorDetailDataJson as any)['conn-1']

  return {
    connector,
    errors400: connector.errors400,
    errors500: connector.errors500,
    totalRequests: connector.requests,
    lastRequestStatus: connector.lastRequestStatus,
    errorRecords: detailData.errorRecords || [],
  }
}

/**
 * Fetch connector error records with pagination
 * Currently returns mock data, will be replaced with API call
 * 
 * @param connectorId - The connector ID
 * @param timeFilter - Time filter for records
 * @param page - Page number
 * @param limit - Items per page
 * @returns Paginated error records
 */
export async function fetchConnectorErrorRecords(
  connectorId: string,
  timeFilter: TimeFilter,
  page: number = 1,
  limit: number = 10,
): Promise<PaginatedResponse<ConnectorErrorRecord>> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 200))

  // FOR DEVELOPMENT: Return mock data
  // In production: return axios.get(`/api/connectors/${connectorId}/errors`, { params: { timeFilter, page, limit } })

  // Get mock detail data
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const detailData = (connectorDetailDataJson as any)[connectorId] || (connectorDetailDataJson as any)['conn-1']
  const allRecords = detailData.errorRecords || []

  // Apply pagination
  const startIndex = (page - 1) * limit
  const endIndex = startIndex + limit
  const paginated = allRecords.slice(startIndex, endIndex)

  return {
    data: paginated,
    meta: {
      totalItems: allRecords.length,
      totalPages: Math.ceil(allRecords.length / limit),
      currentPage: page,
      itemsPerPage: limit,
    },
  }
}

/**
 * Fetch connector chart data for error visualization
 * Currently returns mock data, will be replaced with API call
 * 
 * @param connectorId - The connector ID
 * @param timeFilter - Time filter for chart data
 * @returns Chart data with categories and error counts
 */
export async function fetchConnectorChartData(
  connectorId: string,
  timeFilter: TimeFilter,
): Promise<{ categories: string[]; errors400: number[]; errors500: number[] }> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 200))

  // FOR DEVELOPMENT: Return mock data
  // In production: return axios.get(`/api/connectors/${connectorId}/chart`, { params: { timeFilter } })

  // Get mock detail data
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const detailData = (connectorDetailDataJson as any)[connectorId] || (connectorDetailDataJson as any)['conn-1']
  
  return detailData.chartData[timeFilter] || detailData.chartData['7d']
}

