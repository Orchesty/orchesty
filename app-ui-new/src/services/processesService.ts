import type { PaginatedResponse } from '@/types/api'
import type { Process, ProcessQueryParams } from '@/types/processes'
import processesDataJson from '@/assets/mock-data/processes-data.json'

/**
 * Fetch processes with filters, sorting, and pagination
 * Currently returns filtered mock data, will be replaced with API call
 * 
 * @param params - Query parameters for filtering, sorting, and pagination
 * @returns Paginated response with processes data
 */
export async function fetchProcesses(
  params: ProcessQueryParams,
): Promise<PaginatedResponse<Process>> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 300))

  // FOR DEVELOPMENT: Filter mock data
  // In production: return axios.get('/api/processes', { params: buildQueryParams(params) })

  let filtered = [...(processesDataJson.data as Process[])]

  // Apply status filter
  if (params.status && params.status !== 'all') {
    filtered = filtered.filter((p) => p.status === params.status)
  }

  // Apply topology filter
  if (params.topology && params.topology !== 'all') {
    filtered = filtered.filter((p) => p.topology === params.topology)
  }

  // Apply datetime range filter
  // NOTE: In production, backend will filter processes based on this datetime range
  // For mock data, we just log the range without actual filtering
  if (params.dateFrom || params.dateTo) {
    console.log('Processes datetime filter:', {
      from: params.dateFrom,
      to: params.dateTo,
    })
    // TODO: Backend will filter processes for this datetime range
  }

  // Apply sorting
  if (params.sort && params.order) {
    filtered.sort((a, b) => {
      const aVal = a[params.sort as keyof Process] as number | string
      const bVal = b[params.sort as keyof Process] as number | string
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
 * Fetch list of topology names for filter dropdown
 * Currently returns mock data from topologies
 * 
 * @returns Array of topology names
 */
export async function fetchTopologyNames(): Promise<string[]> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 200))

  // Extract unique topologies from processes
  const topologies = [...new Set(processesDataJson.data.map((p) => p.topology))]
  
  return topologies.sort()
}

