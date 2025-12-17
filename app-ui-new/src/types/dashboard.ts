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
}

export interface ProcessesChartData {
  series: HeatmapSeries[]
  xCategories: string[]
  yCategories: string[]
  totalProcesses: number
  totalFailed: number
  timeRange: string
}

