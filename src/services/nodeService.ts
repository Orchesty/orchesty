import api from '@/services/api'

/**
 * Persists a new RabbitMQ consumer prefetch on the given node. The change
 * is stored in `Node.systemConfigs` immediately, but the running bridge
 * only picks it up after a republish. The backend marks the parent
 * topology as `bridgeOutOfSync = true`, which surfaces as a banner in the
 * editor so the user can republish on their own schedule.
 */
export async function updateNodePrefetch(nodeId: string, prefetch: number): Promise<void> {
  await api.patch(`/api/nodes/${nodeId}`, { prefetch })
}
