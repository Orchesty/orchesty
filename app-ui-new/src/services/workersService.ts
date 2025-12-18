import type { Worker, WorkerQueryParams } from '@/types/settings'
import workersDataJson from '@/assets/mock-data/workers-data.json'

// Mock data
let workersData = [...workersDataJson.data] as Worker[]

/**
 * Fetch workers with pagination and filtering
 */
export async function fetchWorkers(params: WorkerQueryParams = {}) {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 300))

  const {
    page = 1,
    perPage = 10,
    sortBy = 'name',
    sortOrder = 'asc',
    search = '',
  } = params

  // Filter by search
  let filteredData = [...workersData]
  if (search) {
    const searchLower = search.toLowerCase()
    filteredData = filteredData.filter(
      (worker) =>
        worker.name.toLowerCase().includes(searchLower) ||
        worker.url.toLowerCase().includes(searchLower)
    )
  }

  // Sort
  filteredData.sort((a, b) => {
    const aValue = String(a[sortBy as keyof Worker] || '')
    const bValue = String(b[sortBy as keyof Worker] || '')
    const comparison = aValue.localeCompare(bValue)
    return sortOrder === 'asc' ? comparison : -comparison
  })

  // Paginate
  const totalItems = filteredData.length
  const totalPages = Math.ceil(totalItems / perPage)
  const startIndex = (page - 1) * perPage
  const endIndex = startIndex + perPage
  const paginatedData = filteredData.slice(startIndex, endIndex)

  return {
    data: paginatedData,
    meta: {
      page,
      perPage,
      totalItems,
      totalPages,
    },
  }
}

/**
 * Create a new worker
 */
export async function createWorker(data: Omit<Worker, 'id'>): Promise<Worker> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 500))

  const newWorker: Worker = {
    id: `worker-${Date.now()}`,
    ...data,
  }

  workersData.push(newWorker)

  return newWorker
}

/**
 * Update an existing worker
 */
export async function updateWorker(
  id: string,
  data: Partial<Worker>
): Promise<Worker> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 500))

  const index = workersData.findIndex((w) => w.id === id)
  if (index === -1) {
    throw new Error(`Worker with id ${id} not found`)
  }

  workersData[index] = {
    ...workersData[index],
    ...data,
  }

  return workersData[index]
}

/**
 * Delete a worker
 */
export async function deleteWorker(id: string): Promise<void> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 400))

  const index = workersData.findIndex((w) => w.id === id)
  if (index === -1) {
    throw new Error(`Worker with id ${id} not found`)
  }

  workersData.splice(index, 1)
}

