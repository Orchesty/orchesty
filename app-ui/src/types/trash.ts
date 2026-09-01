import type { QueryParams } from './api'

export interface TrashItem {
  id: string
  topology: string
  topologyId: string
  node: string
  nodeId: string
  correlationId: string
  timestamp: string
  headers: Record<string, unknown>
  body: Record<string, unknown>
}

export interface TrashQueryParams {
  page?: number
  perPage?: number
  sortBy?: string
  sortOrder?: 'asc' | 'desc'
  search?: string
  correlationId?: string
  node?: string | string[]
  topology?: string
  timeRange?: string
  dateFrom?: string
  dateTo?: string
}

// API-specific types
export interface FilterCondition {
  column: string
  operator: string  // 'EQ', 'NEQ', 'LIKE', etc.
  value: string[]
}

export interface SorterDefinition {
  column: string
  direction: 'ASC' | 'DESC'
}

export interface PagingDefinition {
  itemsPerPage: number
  page: number
}

export interface TrashApiFilter {
  search: string | null
  filter: FilterCondition[][]  // Array of filter groups
  sorter: SorterDefinition[]
  paging: PagingDefinition
}

// API response item structure
export interface TrashItemApi {
  id: string
  nodeId: string
  topologyId: string
  correlationId: string
  type: string
  created: string  // ISO date string
  message: string
  body: string  // JSON string that needs parsing
  headers: Record<string, unknown>  // Already an object
}// API response structure
export interface TrashApiResponse {
  items: TrashItemApi[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  filter: FilterCondition[][]
  search: string | null
  sorter: SorterDefinition[]
}

// Topology/Node/Application mappings response
export interface TopologyNodeMappings {
  applications: Record<string, string>
  nodes: Record<string, string>
  topologies: Record<string, string>
  topologyVersions?: Record<string, number>
  topologyTree: Record<string, string[]>
  applicationTree?: Record<string, string[]>
}
