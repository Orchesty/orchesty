import type { LogEntry, LogQueryParams, LogSeverity } from '@/types/logs'
import logsDataJson from '@/assets/mock-data/logs-data.json'

interface LogsResponse {
  data: LogEntry[]
  pagination: {
    total: number
    page: number
    perPage: number
    totalPages: number
  }
}

export async function fetchLogs(params: LogQueryParams = {}): Promise<LogsResponse> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 300 + Math.random() * 200))

  let filteredData = [...logsDataJson.data] as LogEntry[]

  // Apply search filter
  if (params.search) {
    const searchLower = params.search.toLowerCase()
    filteredData = filteredData.filter(
      (log) =>
        log.message.toLowerCase().includes(searchLower) ||
        log.node.toLowerCase().includes(searchLower) ||
        log.nodeId.toLowerCase().includes(searchLower) ||
        log.correlationId.toLowerCase().includes(searchLower),
    )
  }

  // Apply severity filter
  if (params.severity) {
    filteredData = filteredData.filter((log) => log.severity === params.severity)
  }

  // Apply topology filter
  if (params.topology) {
    filteredData = filteredData.filter((log) => log.topology === params.topology)
  }

  // Apply sorting
  if (params.sortBy) {
    filteredData.sort((a, b) => {
      const aValue = a[params.sortBy as keyof LogEntry]
      const bValue = b[params.sortBy as keyof LogEntry]

      if (aValue === undefined || bValue === undefined) return 0

      let comparison = 0
      if (aValue < bValue) comparison = -1
      if (aValue > bValue) comparison = 1

      return params.sortOrder === 'asc' ? comparison : -comparison
    })
  }

  // Apply pagination
  const page = params.page || 1
  const perPage = params.perPage || 15
  const totalItems = filteredData.length
  const totalPages = Math.ceil(totalItems / perPage)
  const startIndex = (page - 1) * perPage
  const endIndex = startIndex + perPage
  const paginatedData = filteredData.slice(startIndex, endIndex)

  return {
    data: paginatedData,
    pagination: {
      total: totalItems,
      page,
      perPage,
      totalPages,
    },
  }
}

export async function fetchTopologyNamesForLogs(): Promise<string[]> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 100))

  const topologies = new Set<string>()
  logsDataJson.data.forEach((log) => topologies.add(log.topology))

  return Array.from(topologies).sort()
}

export async function fetchLogDetail(id: string): Promise<LogEntry | null> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 200))

  const log = logsDataJson.data.find((log) => log.id === id)
  return log ? (log as LogEntry) : null
}

