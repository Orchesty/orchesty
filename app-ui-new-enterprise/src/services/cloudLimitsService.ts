import api from '@/services/api'

export type CloudLimitBand = 'none' | 'warning' | 'critical' | 'exceeded'

export interface CloudLimitsUsage {
  limits: {
    messages: number
    storageGb: number
    topologySlots: number
  }
  usage: {
    messages: number
    storageMb: number
    topologySlots: number
  }
  percent: {
    messages: number | null
    storage: number | null
    topologySlots: number | null
  }
  band: {
    messages: CloudLimitBand
    storage: CloudLimitBand
  }
  updatedAt: string | null
}

export interface CloudLimitsHistoryPoint {
  created: string
  value: number
}

export interface CloudLimitsHistory {
  messages: CloudLimitsHistoryPoint[]
  storage: CloudLimitsHistoryPoint[]
  binMs?: number
}

export async function fetchLimitsUsage(): Promise<CloudLimitsUsage> {
  const response = await api.get('/api/orchesty/metrics/limits-usage')
  return response.data as CloudLimitsUsage
}

export async function fetchLimitsHistory(
  from: string,
  to: string,
  buckets = 60,
): Promise<CloudLimitsHistory> {
  const response = await api.get('/api/orchesty/metrics/limits-history', {
    params: { from, to, buckets },
  })
  return response.data as CloudLimitsHistory
}
