import api from './api'
import type { NotificationSubscription } from '@/types/account'

export async function fetchSubscriptions(): Promise<NotificationSubscription[]> {
  const response = await api.get<NotificationSubscription[]>('/api/notifications/subscriptions')
  return response.data
}

export async function upsertSubscription(
  subscription: Pick<NotificationSubscription, 'event_type' | 'channel' | 'enabled' | 'filters'>,
): Promise<NotificationSubscription[]> {
  const response = await api.put<NotificationSubscription[]>(
    '/api/notifications/subscriptions',
    subscription,
  )
  return response.data
}
