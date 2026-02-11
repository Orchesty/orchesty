export interface ProcessData {
  id: string
  topology: string
  startTime: Date
  result: 'success' | 'failed'
}

export interface ProcessMetrics {
  success: number
  failed: number
}

export interface TimeCategory {
  label: string
  start: Date
  end: Date
}

export interface HeatmapData {
  x: string // time category
  y: number // value (success count or failed count with offset)
  meta: {
    success: number
    failed: number
    isFailed: boolean
  }
}

export interface HeatmapSeries {
  name: string // topology name
  data: HeatmapData[]
}

export type TimeFilter = '1h' | '24h' | '7d' | '30d'
export type ProcessFilter = 'all' | 'failed'

export interface TableColumn {
  key: string
  label: string
  sortable?: boolean
  className?: string
}

export interface LimiterTableRow {
  connector: string
  topology: string
  messages: number
  change: number // percentage
}

export interface LimiterData {
  totalMessages: number
  vsLastDay: number
  chartData: {
    categories: string[]
    series: number[]
  }
  tableData: LimiterTableRow[]
  meta: {
    currentPage: number
    totalPages: number
    totalItems: number
    itemsPerPage: number
  }
}

export interface TrashTableRow {
  topology: string
  node: string
  message: string
  count: number
}

export interface TrashData {
  totalMessages: number
  vsLastDay: number
  chartData: Array<{ x: string; y: number }>
  tableData: TrashTableRow[]
  meta: {
    currentPage: number
    totalPages: number
    totalItems: number
    itemsPerPage: number
  }
}

export interface ProcessesChartData {
  series: HeatmapSeries[]
  xCategories?: string[]
  yCategories?: string[]
  totalProcesses?: number
  failedProcesses?: number
  timeRange?: string
}

export interface HeatmapClickData {
  topology: string
  timeSlot: string
}

export interface ProcessesExternalFilters {
  topology: string | null
  timeRange: {
    from: string
    to: string
  } | null
}

// Limiter API Types
export interface LimiterTotalApiItem {
  count: number
  previousCount: number
}

export interface LimiterTotalApiResponse {
  filter: unknown[]
  items: LimiterTotalApiItem[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  search: string | null
  sorter: unknown[]
}

export interface LimiterGraphApiItem {
  created: string
  count: number
}

export interface LimiterGraphApiResponse {
  filter: unknown[]
  items: LimiterGraphApiItem[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  search: string | null
  sorter: Array<{ column: string; direction: string }>
}

export interface LimiterTableApiItem {
  nodeId: string
  topologyId: string
  count: number
  previousCount: number
}

export interface LimiterTableApiResponse {
  filter: unknown[]
  items: LimiterTableApiItem[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  search: string | null
  sorter: Array<{ column: string; direction: string }>
}

export interface LimiterApiFilter {
  search: string | null
  filter: Array<Array<{ column: string; operator: string; value: unknown[] }>>
  sorter: Array<{ column: string; direction: string }>
  paging: {
    itemsPerPage: number
    page: number
  }
}

// Trash API Types
export interface TrashTotalApiItem {
  count: number
  previousCount: number
}

export interface TrashTotalApiResponse {
  filter: unknown[]
  items: TrashTotalApiItem[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  search: string | null
  sorter: unknown[]
}

export interface TrashGraphApiItem {
  topologyId: string
  count: number
}

export interface TrashGraphApiResponse {
  filter: unknown[]
  items: TrashGraphApiItem[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  search: string | null
  sorter: Array<{ column: string; direction: string }>
}

export interface TrashTableApiItem {
  nodeId: string
  topologyId: string
  message: string | null
  count: number
}

export interface TrashTableApiResponse {
  filter: unknown[]
  items: TrashTableApiItem[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  search: string | null
  sorter: Array<{ column: string; direction: string }>
}

export interface TrashApiFilter {
  search: string | null
  filter: Array<Array<{ column: string; operator: string; value: unknown[] }>>
  sorter: Array<{ column: string; direction: string }>
  paging: {
    itemsPerPage: number
    page: number
  }
}
