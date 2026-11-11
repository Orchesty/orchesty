import api from './api'
import type { ConnectorApiFilter, ConnectorApiResponse } from '@/types/connectors'

// Actions: connectors called directly by an SDK worker (no topology). Orchesty only grants,
// audits (enterprise) and measures them. Their connector metrics use node_id `action:<key>`.

export interface ActionItem {
  /** `<application>.<name>` */
  key: string
  name: string
  app: string
  kind: 'read' | 'write'
  idempotent: boolean
  description: string
  inputSchema: Record<string, unknown>
  timeout: number
  /** SDK worker serving the action */
  sdk: string
}

export interface ActionStats {
  requests: number
  errors400: number
  errors500: number
  avgRequestTime: number
  lastRequestStatus: number
}

export type ActionRow = ActionItem & ActionStats

export const actionNodeId = (key: string): string => `action:${key}`

export async function fetchActions(): Promise<ActionItem[]> {
  const response = await api.get<{ items: ActionItem[] }>('/api/actions')

  return response.data.items ?? []
}

const emptyStats = (): ActionStats => ({
  requests: 0,
  errors400: 0,
  errors500: 0,
  avgRequestTime: 0,
  lastRequestStatus: 0,
})

export async function fetchActionStats(keys: string[], dateFrom?: string): Promise<Record<string, ActionStats>> {
  const result: Record<string, ActionStats> = Object.fromEntries(keys.map(k => [k, emptyStats()]))
  if (keys.length === 0) return result

  const filter: ConnectorApiFilter = {
    search: null,
    filter: [[{ column: 'nodeId', operator: 'EQ', value: keys.map(actionNodeId) }]],
    sorter: [],
    paging: { itemsPerPage: 9999, page: 1 },
  }
  if (dateFrom) {
    filter.filter.push([{ column: 'created', operator: 'GTE', value: [dateFrom] }])
  }

  const response = await api.get<ConnectorApiResponse>('/api/metrics/connectors/overview', {
    params: { filter: JSON.stringify(filter), aggregated: 1 },
  })

  for (const item of response.data.items) {
    const key = item.nodeId.startsWith('action:') ? item.nodeId.substring('action:'.length) : item.nodeId
    if (!result[key]) continue
    result[key] = {
      requests: item.count,
      errors400: item.status400,
      errors500: item.status500,
      avgRequestTime: Math.round(item.duration),
      lastRequestStatus: item.lastStatus ?? 0,
    }
  }

  return result
}
