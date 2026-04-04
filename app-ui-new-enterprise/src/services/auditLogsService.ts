import type { AuditLogEntry, AuditLogQueryParams } from '@/types/audit-logs'
import type { PaginatedResponse } from '@/types/api'
import api from '@/services/api'

function buildTimeRange(timeRange?: string): { from?: string; to?: string } {
  if (!timeRange || timeRange === 'all') return {}

  const now = new Date()
  let from: Date

  switch (timeRange) {
    case 'yesterday': {
      const y = new Date(now)
      y.setDate(y.getDate() - 1)
      y.setHours(0, 0, 0, 0)
      from = y
      break
    }
    case 'today': {
      from = new Date(now)
      from.setHours(0, 0, 0, 0)
      break
    }
    case 'last7days':
      from = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
      break
    case 'last30days':
      from = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000)
      break
    case 'last90days':
      from = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000)
      break
    default:
      if (timeRange.startsWith('custom:')) {
        const parts = timeRange.split(':')
        return {
          from: parts[1] ? new Date(parts[1]).toISOString() : undefined,
          to: parts[2] ? new Date(parts[2]).toISOString() : undefined,
        }
      }
      return {}
  }

  return {
    from: from.toISOString(),
    to: now.toISOString(),
  }
}

/**
 * Fetch audit log entries with filtering, sorting, and pagination
 */
export async function fetchAuditLogs(
  params: AuditLogQueryParams
): Promise<PaginatedResponse<AuditLogEntry>> {
  const { from, to } = buildTimeRange(params.timeRange)

  const filter: Record<string, string> = {}
  if (params.search) filter.search = params.search
  if (from) filter.from = from
  if (to) filter.to = to

  const queryParams: Record<string, string | number> = {
    page: params.page || 1,
    limit: params.limit || 20,
  }

  if (params.sort) queryParams.sort = params.sort
  if (params.order) queryParams.order = params.order
  if (Object.keys(filter).length > 0) queryParams.filter = JSON.stringify(filter)

  const response = await api.get('/api/audit-logs', { params: queryParams })
  const body = response.data

  const total = body.total ?? 0
  const page = body.page ?? 1
  const limit = body.limit ?? 20

  return {
    data: body.items ?? [],
    meta: {
      totalItems: total,
      currentPage: page,
      itemsPerPage: limit,
      totalPages: Math.ceil(total / limit) || 1,
    },
  }
}

/**
 * Fetch a single audit log entry by ID
 */
export async function fetchAuditLogDetail(id: string): Promise<AuditLogEntry | null> {
  try {
    const response = await api.get(`/api/audit-logs/${id}`)
    return response.data as AuditLogEntry
  } catch {
    return null
  }
}

/**
 * Export audit logs as CSV
 */
export async function exportAuditLogs(params: AuditLogQueryParams): Promise<Blob> {
  const allItems: AuditLogEntry[] = []
  let page = 1
  const limit = 100
  let hasMore = true

  while (hasMore) {
    const response = await fetchAuditLogs({ ...params, page, limit })
    allItems.push(...response.data)
    hasMore = response.data.length === limit
    page++

    if (page > 100) break
  }

  const csvHeader = 'Timestamp,User,Object,Action,Note\n'
  const csvRows = allItems
    .map(
      (log) =>
        `"${log.timestamp}","${log.user}","${log.object}","${log.action}","${log.note}"`
    )
    .join('\n')

  const csvContent = csvHeader + csvRows
  return new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
}
