import type { PaginatedResponse } from '@/types/api'
import type { Topology, TopologyQueryParams } from '@/types/topologies'
import topologiesDataJson from '@/assets/mock-data/topologies-data.json'

/**
 * Fetch topologies with filters, sorting, and pagination
 * Currently returns filtered mock data, will be replaced with API call
 * 
 * @param params - Query parameters for filtering, sorting, and pagination
 * @returns Paginated response with topologies data
 */
export async function fetchTopologies(
  params: TopologyQueryParams,
): Promise<PaginatedResponse<Topology>> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 300))

  // FOR DEVELOPMENT: Filter mock data
  // In production: return axios.get('/api/topologies', { params: buildQueryParams(params) })

  let filtered = [...(topologiesDataJson.data as Topology[])]

  // Apply status filter
  if (params.status && params.status !== 'all') {
    filtered = filtered.filter((t) => t.lastRunStatus === params.status)
  }

  // Apply time range filter (placeholder - in real app this would filter by date)
  // For now, we just pass it through without actual filtering
  if (params.timeRange) {
    // TODO: Implement actual time range filtering when API is ready
    console.log('Time range filter:', params.timeRange)
  }

  // Apply sorting
  if (params.sort && params.order) {
    filtered.sort((a, b) => {
      const aVal = a[params.sort as keyof Topology] as number | string
      const bVal = b[params.sort as keyof Topology] as number | string
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
