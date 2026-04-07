import api from '@/services/api'

export interface BridgeItem {
  _id: string
  name: string
  version: number
  enabled: boolean
  runningProcesses: number
  trashCount: number
}

export interface BridgesSummary {
  total: number
  reducible: number
}

export interface BridgesResponse {
  items: BridgeItem[]
  summary: BridgesSummary
}

export async function fetchRunningBridges(): Promise<BridgesResponse> {
  const response = await api.get('/api/resources/bridges')
  return response.data as BridgesResponse
}

export async function decommissionBridge(topologyId: string, forceCleanup: boolean): Promise<void> {
  await api.delete(`/api/resources/bridges/${topologyId}`, {
    params: forceCleanup ? { forceCleanup: 'true' } : {},
  })
}

export async function restartBridge(topologyId: string): Promise<void> {
  await api.post(`/api/resources/bridges/${topologyId}/restart`)
}

export async function terminateProcesses(topologyId: string, correlationId?: string): Promise<void> {
  await api.post(`/api/resources/bridges/${topologyId}/terminate`, correlationId ? { correlationId } : {})
}
