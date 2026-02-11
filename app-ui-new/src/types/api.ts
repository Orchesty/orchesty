export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    totalItems: number
    totalPages: number
    currentPage: number
    itemsPerPage: number
  }
}

export interface SortConfig {
  field: string
  direction: 'asc' | 'desc'
}

export interface QueryParams {
  page?: number
  limit?: number
  sort?: string
  order?: 'asc' | 'desc'
  [key: string]: string | number | undefined
}

