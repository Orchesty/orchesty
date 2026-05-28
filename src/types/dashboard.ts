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
  _nodeId?: string
  _nodeIds?: string[]
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
  nodeId: string
  topologyId: string
  applicationId: string
  connector: string
  topology: string
  application: string
  limitSetting: string
  // Per-node peak per-minute hold within the selected time window.
  // Same algorithm as `LimiterData.maxMessages`, just per-node.
  maxMessages: number
  // Live snapshot of the limiter queue right now (filled in by the
  // component after merging the snapshot response).
  liveMessages?: number
}

export interface LimiterData {
  // Cross-node peak per-minute hold within the selected time window.
  maxMessages: number
  // Live snapshot total — sum of all per-node holds in the limiter queue
  // right now. Optional because snapshot fetch can fail independently of
  // the metrics queries.
  liveTotalMessages?: number
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
  topologyId: string
  nodeId: string
  topology: string
  node: string
  message: string
  count: number
}

export interface TrashData {
  totalMessages: number
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
  timeSlotEnd: string
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
  // Last per-minute sum within the 90s validity window. Not surfaced in the
  // UI anymore (used to back the "actual" pill), but kept in the type so
  // ad-hoc consumers can still read it from the raw response.
  count: number
  // Peak per-minute sum across the selected time window — drives the
  // "Max" headline in `LimiterCard` / `LimiterTab`.
  maximumCount: number
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
  applicationId: string
  // Per-node peak per-minute hold within the selected time window
  // (same algorithm as LimiterTotalApiItem.maximumCount, just per-node).
  maximumCount: number
}

export interface LimiterApplicationApiItem {
  applicationId: string
  // Per-application peak per-minute hold within the selected time window.
  // Same algorithm as LimiterTotalApiItem.maximumCount, just grouped by
  // applicationId — guaranteed to be ≤ headline `maximumCount`.
  maximumCount: number
}

export interface LimiterApplicationApiResponse {
  filter: unknown[]
  items: LimiterApplicationApiItem[]
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

// Application limiter settings
export interface AppLimiterSetting {
  name: string
  useLimit: boolean
  value: number | null
  time: number | null
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

// Connector Heatmap API Types
export interface ConnectorHeatmapApiItem {
  created: string
  nodeId: string
  applicationId: string
  success: number
  failed: number
}

export interface ConnectorHeatmapApiResponse {
  filter: unknown[]
  items: ConnectorHeatmapApiItem[]
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

export interface ConnectorHeatmapData {
  series: HeatmapSeries[]
  xCategories: string[]
  totalRequests: number
  totalFailed: number
  /** nodeId -> applicationId mapping (raw IDs, resolved to names in component) */
  nodeAppMap: Map<string, string>
}

export type { TrashApiFilter } from './trash'
