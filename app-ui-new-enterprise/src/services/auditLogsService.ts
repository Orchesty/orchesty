import type { AuditLogEntry, AuditLogQueryParams } from '@/types/audit-logs'
import type { PaginatedResponse } from '@/types/api'
import auditLogsDataJson from '@/assets/mock-data/audit-logs-data.json'

/**
 * Fetch audit log entries with filtering, sorting, and pagination
 */
export async function fetchAuditLogs(
  params: AuditLogQueryParams
): Promise<PaginatedResponse<AuditLogEntry>> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 300))

  let filtered = [...auditLogsDataJson.data] as AuditLogEntry[]

  // Filter by search (user or object)
  if (params.search && params.search.trim() !== '') {
    const searchLower = params.search.toLowerCase()
    filtered = filtered.filter(
      (log) =>
        log.user.toLowerCase().includes(searchLower) ||
        log.object.toLowerCase().includes(searchLower)
    )
  }

  // Filter by time range (mock - in real app would filter by timestamp)
  if (params.timeRange && params.timeRange !== 'all') {
    // For now, just return all data. In real app, filter by timestamp
    // based on params.timeRange (yesterday, today, last7days, etc.)
  }

  // Sorting
  if (params.sort) {
    filtered.sort((a, b) => {
      const aVal = a[params.sort as keyof AuditLogEntry]
      const bVal = b[params.sort as keyof AuditLogEntry]

      if (typeof aVal === 'string' && typeof bVal === 'string') {
        const comparison = aVal.localeCompare(bVal)
        return params.order === 'desc' ? -comparison : comparison
      }

      return 0
    })
  }

  // Pagination
  const page = params.page || 1
  const limit = params.limit || 20
  const start = (page - 1) * limit
  const end = start + limit
  const paginatedData = filtered.slice(start, end)

  return {
    data: paginatedData,
    meta: {
      total: filtered.length,
      page,
      limit,
      totalPages: Math.ceil(filtered.length / limit),
    },
  }
}

/**
 * Fetch a single audit log entry by ID
 */
export async function fetchAuditLogDetail(id: string): Promise<AuditLogEntry | null> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 200))

  const log = auditLogsDataJson.data.find((log) => log.id === id)
  return log ? (log as AuditLogEntry) : null
}

/**
 * Export audit logs (mock function)
 */
export async function exportAuditLogs(params: AuditLogQueryParams): Promise<Blob> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 500))

  // In a real app, this would call an API endpoint and return a CSV/Excel file
  const logs = await fetchAuditLogs(params)
  
  // Create a simple CSV
  const csvHeader = 'Timestamp,User,Object,Action,Note\n'
  const csvRows = logs.data
    .map(
      (log) =>
        `"${log.timestamp}","${log.user}","${log.object}","${log.action}","${log.note}"`
    )
    .join('\n')
  
  const csvContent = csvHeader + csvRows
  return new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
}

