import api from './api'
import type {
  Worker,
  WorkerQueryParams,
  WorkerHeaderItem,
  WorkerApiResponse,
  WorkersListResponse,
} from '@/types/settings'

// Convert API headers array to component object format
function headersArrayToObject(headers: WorkerHeaderItem[]): Record<string, string> {
  return headers.reduce((acc, { key, value }) => {
    acc[key] = value
    return acc
  }, {} as Record<string, string>)
}

// Convert component object to API headers array format
function headersObjectToArray(headers: Record<string, string>): WorkerHeaderItem[] {
  return Object.entries(headers).map(([key, value]) => ({ key, value }))
}

// Convert API response to component Worker type
function mapApiWorkerToWorker(apiWorker: WorkerApiResponse): Worker {
  return {
    id: apiWorker.id,
    name: apiWorker.name,
    url: apiWorker.url,
    headers: headersArrayToObject(apiWorker.headers),
  }
}

/**
 * Fetch workers with pagination and filtering
 */
export async function fetchWorkers(params: WorkerQueryParams = {}) {
  const {
    page = 1,
    limit = 10,
    sort = 'name',
    order = 'asc',
    search = '',
  } = params

  // Note: Backend pagination might work differently
  // Adjust query params based on actual API requirements
  const response = await api.get<WorkersListResponse>('/api/sdks', {
    params: {
      page,
      itemsPerPage: limit,
      // Add other params if backend supports them
      ...(search && { search }),
      ...(sort && { sortBy: sort }),
      ...(order && { sortOrder: order }),
    },
  })

  const data = response.data

  return {
    data: data.items.map(mapApiWorkerToWorker),
    meta: {
      page: data.paging.page,
      perPage: data.paging.itemsPerPage,
      totalItems: data.paging.total,
      totalPages: data.paging.lastPage,
    },
  }
}

/**
 * Create a new worker
 */
export async function createWorker(data: Omit<Worker, 'id'>): Promise<Worker> {
  const requestData = {
    name: data.name,
    url: data.url,
    headers: headersObjectToArray(data.headers),
  }

  const response = await api.post<WorkerApiResponse>('/api/sdks', requestData)

  return mapApiWorkerToWorker(response.data)
}

/**
 * Update an existing worker
 */
export async function updateWorker(id: string, data: Partial<Worker>): Promise<Worker> {
  const requestData = {
    id,
    ...(data.name !== undefined && { name: data.name }),
    ...(data.url !== undefined && { url: data.url }),
    ...(data.headers !== undefined && { headers: headersObjectToArray(data.headers) }),
  }

  const response = await api.put<WorkerApiResponse>(`/api/sdks/${id}`, requestData)

  return mapApiWorkerToWorker(response.data)
}

/**
 * Delete a worker
 */
export async function deleteWorker(id: string): Promise<void> {
  // Backend expects id in the request body for DELETE
  await api.delete(`/api/sdks/${id}`, {
    data: { id },
  })
}
