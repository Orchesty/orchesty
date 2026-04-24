import api from '@/services/api'
import type { TraceReport } from '@/types/trace'

interface TraceReportApiItem {
  id: string
  title: string
  contentHtml: string
  messageId?: string | null
  createdAt: string
  updatedAt: string
  userId: string
}

interface TraceReportListResponse {
  items: TraceReportApiItem[]
  page: number
  limit: number
  total: number
}

const toReport = (item: TraceReportApiItem): TraceReport => ({
  id: item.id,
  title: item.title,
  content: item.contentHtml,
  timestamp: new Date(item.createdAt),
  messageId: item.messageId ?? '',
})

export const fetchReports = async (): Promise<TraceReport[]> => {
  const response = await api.get<TraceReportListResponse>('/api/trace-reports', {
    params: { page: 1, limit: 200 },
  })
  return response.data.items.map(toReport)
}

export const saveReport = async (report: Omit<TraceReport, 'id'>): Promise<TraceReport> => {
  const response = await api.post<TraceReportApiItem>('/api/trace-reports', {
    title: report.title,
    contentHtml: report.content,
    messageId: report.messageId || null,
  })
  return toReport(response.data)
}

export const updateReportTitle = async (id: string, title: string): Promise<void> => {
  await api.patch(`/api/trace-reports/${id}`, { title })
}

export const deleteReport = async (id: string): Promise<void> => {
  await api.delete(`/api/trace-reports/${id}`)
}
