import type { PaginatedResponse } from '@/types/api'
import type { Topology, TopologyQueryParams } from '@/types/topologies'
import type { TopologyDetail, TopologyVersion } from '@/types/topologies-page'
import topologiesDataJson from '@/assets/mock-data/topologies-data.json'
import topologiesDetailData from '@/assets/mock-data/topologies-detail-data.json'

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

  // Apply datetime range filter
  // NOTE: In production, backend will aggregate data (processesRun, failedProcesses, etc.) based on this datetime range
  // For mock data, we just log the range without actual filtering
  if (params.dateFrom || params.dateTo) {
    console.log('Topologies datetime filter:', {
      from: params.dateFrom,
      to: params.dateTo,
    })
    // TODO: Backend will filter/aggregate topology statistics for this datetime range
  }

  // @deprecated - timeRange is replaced by dateFrom/dateTo
  if (params.timeRange) {
    console.log('Time range filter (deprecated):', params.timeRange)
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

/**
 * Fetch topology detail including all versions
 * 
 * @param topologyId - The topology ID
 * @param versionId - Optional specific version ID to load
 * @returns Topology detail with all versions
 */
export async function fetchTopologyDetail(
  topologyId: string,
  versionId?: string
): Promise<TopologyDetail> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 300))

  // FOR DEVELOPMENT: Get mock data
  // In production: return axios.get(`/api/topologies/${topologyId}`, { params: { version: versionId } })

  const topologyData = topologiesDetailData[topologyId as keyof typeof topologiesDetailData]
  
  if (!topologyData) {
    throw new Error(`Topology with ID ${topologyId} not found`)
  }

  // If a specific version is requested, update the current version data
  if (versionId) {
    const selectedVersion = topologyData.versions.find(v => v.id === versionId)
    if (selectedVersion) {
      return {
        ...topologyData,
        version: selectedVersion.version,
        visibility: selectedVersion.visibility,
        status: selectedVersion.status
      }
    }
  }

  return topologyData as TopologyDetail
}

/**
 * Fetch all versions for a topology
 * 
 * @param topologyId - The topology ID
 * @returns List of all versions for the topology
 */
export async function fetchTopologyVersions(
  topologyId: string
): Promise<TopologyVersion[]> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 200))

  // FOR DEVELOPMENT: Get mock data
  // In production: return axios.get(`/api/topologies/${topologyId}/versions`)

  const topologyData = topologiesDetailData[topologyId as keyof typeof topologiesDetailData]
  
  if (!topologyData) {
    throw new Error(`Topology with ID ${topologyId} not found`)
  }

  return topologyData.versions
}

/**
 * Switch to a different version of a topology
 * This is a mock function - in production would trigger backend version switch
 * 
 * @param topologyId - The topology ID
 * @param versionId - The version ID to switch to
 */
export async function switchTopologyVersion(
  topologyId: string,
  versionId: string
): Promise<void> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 500))

  // FOR DEVELOPMENT: Mock version switch
  // In production: return axios.post(`/api/topologies/${topologyId}/versions/${versionId}/switch`)

  console.log(`Switching topology ${topologyId} to version ${versionId}`)
  
  // Mock validation
  const topologyData = topologiesDetailData[topologyId as keyof typeof topologiesDetailData]
  if (!topologyData) {
    throw new Error(`Topology with ID ${topologyId} not found`)
  }

  const version = topologyData.versions.find(v => v.id === versionId)
  if (!version) {
    throw new Error(`Version ${versionId} not found for topology ${topologyId}`)
  }
}
