import api from '@/services/api'

export interface ProcessDetailTopology {
  name: string
  breakpointCount: number
  trashCount: number
  limiterCount: number
  repeaterCount: number
}

export interface ProcessDetailNode {
  name: string
  processTime: number | null
  requestTime: number | null
  breakpointCount: number
  trashCount: number
  limiterCount: number
  repeaterCount: number
}

export interface ProcessDetail {
  id: string
  started: string
  finished: string | null
  duration: number
  status: string
  okCount: number
  nokCount: number
  totalCount: number
  topology: ProcessDetailTopology
  nodes: Record<string, ProcessDetailNode>
}

export async function fetchProcessDetail(id: string): Promise<ProcessDetail> {
  const response = await api.get<ProcessDetail>(`/api/processes/${id}`)
  return response.data
}
