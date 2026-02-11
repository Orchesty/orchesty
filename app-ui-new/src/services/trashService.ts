import type { TrashItem, TrashQueryParams } from '@/types/trash'
import trashDataJson from '@/assets/mock-data/trash-data.json'

interface PaginatedResponse<T> {
  data: T[]
  pagination: {
    page: number
    perPage: number
    total: number
    totalPages: number
  }
}

// Simulate API delay
const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))

/**
 * Fetch trash items with filtering, sorting, and pagination
 */
export async function fetchTrashItems(
  params: TrashQueryParams
): Promise<PaginatedResponse<TrashItem>> {
  await delay(300)

  let filteredData = [...trashDataJson.data]

  // Filter by correlation ID (partial match)
  if (params.correlationId) {
    const searchTerm = params.correlationId.toLowerCase()
    filteredData = filteredData.filter((item) =>
      item.correlationId.toLowerCase().includes(searchTerm)
    )
  }

  // Filter by node (partial match)
  if (params.node) {
    const searchTerm = params.node.toLowerCase()
    filteredData = filteredData.filter((item) => item.node.toLowerCase().includes(searchTerm))
  }

  // Filter by topology
  if (params.topology) {
    filteredData = filteredData.filter((item) => item.topology === params.topology)
  }

  // Filter by date range
  if (params.dateFrom || params.dateTo) {
    filteredData = filteredData.filter((item) => {
      const itemDate = new Date(item.timestamp)
      if (params.dateFrom && itemDate < new Date(params.dateFrom)) return false
      if (params.dateTo && itemDate > new Date(params.dateTo)) return false
      return true
    })
  }

  // Sorting
  if (params.sortBy) {
    filteredData.sort((a, b) => {
      const aValue = a[params.sortBy as keyof TrashItem]
      const bValue = b[params.sortBy as keyof TrashItem]

      if (aValue === undefined || bValue === undefined) return 0

      let comparison = 0
      if (aValue < bValue) comparison = -1
      if (aValue > bValue) comparison = 1

      return params.sortOrder === 'desc' ? -comparison : comparison
    })
  }

  // Pagination
  const page = params.page || 1
  const perPage = params.perPage || 10
  const total = filteredData.length
  const totalPages = Math.ceil(total / perPage)
  const startIndex = (page - 1) * perPage
  const endIndex = startIndex + perPage
  const paginatedData = filteredData.slice(startIndex, endIndex)

  return {
    data: paginatedData,
    pagination: {
      page,
      perPage,
      total,
      totalPages,
    },
  }
}

/**
 * Get unique topology names for filter dropdown
 */
export async function fetchTopologyNames(): Promise<string[]> {
  await delay(100)
  const uniqueTopologies = [...new Set(trashDataJson.data.map((item) => item.topology))]
  return uniqueTopologies.sort()
}

/**
 * Approve a trash item (mock)
 */
export async function approveTrashItem(id: string): Promise<void> {
  await delay(500)
  console.log(`Mock: Approved trash item ${id}`)
}

/**
 * Reject a trash item (mock)
 */
export async function rejectTrashItem(id: string): Promise<void> {
  await delay(500)
  console.log(`Mock: Rejected trash item ${id}`)
}

/**
 * Update a trash item (mock)
 */
export async function updateTrashItem(
  id: string,
  data: Partial<TrashItem>
): Promise<void> {
  await delay(500)
  console.log(`Mock: Updated trash item ${id}`, data)
}

/**
 * Bulk approve trash items (mock)
 */
export async function bulkApprove(ids: string[]): Promise<void> {
  await delay(800)
  console.log(`Mock: Bulk approved ${ids.length} items`, ids)
}

/**
 * Bulk reject trash items (mock)
 */
export async function bulkReject(ids: string[]): Promise<void> {
  await delay(800)
  console.log(`Mock: Bulk rejected ${ids.length} items`, ids)
}

