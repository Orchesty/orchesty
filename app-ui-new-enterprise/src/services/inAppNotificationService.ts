import api from '@/services/api'

export interface InAppNotificationItem {
  id: string
  tenant_id: string
  event_type: string
  severity: string
  message: string
  topology_id: string | null
  topology_name: string | null
  node_name: string | null
  created_at: string
}

export interface NotificationListResponse {
  data: InAppNotificationItem[]
  total: number
  page: number
  limit: number
}

export interface NotificationFilters {
  page?: number
  limit?: number
  severity?: string
  from?: string
  to?: string
}

export async function getNotifications(
  filters: NotificationFilters = {},
): Promise<NotificationListResponse> {
  const params: Record<string, string | number> = {}

  if (filters.page) params.page = filters.page
  if (filters.limit) params.limit = filters.limit
  if (filters.severity) params.severity = filters.severity
  if (filters.from) params.from = filters.from
  if (filters.to) params.to = filters.to

  const response = await api.get('/api/notifications/in-app', { params })
  return response.data
}

export async function getNotificationCount(since?: string): Promise<number> {
  const params: Record<string, string> = {}
  if (since) params.since = since

  const response = await api.get('/api/notifications/in-app/count', { params })
  return response.data.count
}
