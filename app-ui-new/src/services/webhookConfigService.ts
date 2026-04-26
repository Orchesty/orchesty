import api from '@/services/api'

export interface WebhookEvent {
  name: string
  parameters: Record<string, string>
  description?: string
}

export interface WebhookCatalogApp {
  application: string
  name: string
  logo: string
  events: WebhookEvent[]
}

export interface WebhookCatalogEntry extends WebhookCatalogApp {
  sdk: string
}

export interface WebhookConfigItem {
  id?: string
  topologyName: string
  nodeName: string
  application: string
  user: string
  sdk: string
  eventName: string
  parameters: Record<string, string>
  enabled: boolean
  registered: boolean
  webhookId: string
  token: string
  unsubscribeFailed: boolean
  orphan?: boolean
  created?: string
  updated?: string
}

export interface CascadeResultItem {
  topologyName: string
  nodeName: string
  status: 'ok' | 'error'
  message?: string
  payload?: unknown
}

export async function listWebhookConfigs(topologyName: string): Promise<WebhookConfigItem[]> {
  const { data } = await api.get<{ items: WebhookConfigItem[] }>(
    `/api/topologies/by-name/${encodeURIComponent(topologyName)}/webhook-configs`,
  )
  return data.items ?? []
}

export async function upsertWebhookConfig(
  topologyName: string,
  nodeName: string,
  payload: {
    application: string
    user: string
    sdk: string
    eventName: string
    parameters?: Record<string, string>
    enabled?: boolean
  },
): Promise<WebhookConfigItem> {
  const { data } = await api.put<WebhookConfigItem>(
    `/api/topologies/by-name/${encodeURIComponent(topologyName)}/nodes/${encodeURIComponent(nodeName)}/webhook-config`,
    payload,
  )
  return data
}

export async function deleteWebhookConfig(topologyName: string, nodeName: string): Promise<void> {
  await api.delete(
    `/api/topologies/by-name/${encodeURIComponent(topologyName)}/nodes/${encodeURIComponent(nodeName)}/webhook-config`,
  )
}

// Subscribe a webhook node to its external API. The optional `parameters`
// argument is forwarded to the SDK's webhook subscribe call (filters, source,
// etc. — fully application specific). The backend creates the underlying
// WebhookConfig document on first call; the user never sees that detail.
export async function subscribeWebhookConfig(
  topologyName: string,
  nodeName: string,
  parameters?: Record<string, unknown>,
): Promise<{ status: string; payload: unknown }> {
  const body = parameters !== undefined ? { parameters } : {}
  const { data } = await api.post<{ status: string; payload: unknown }>(
    `/api/topologies/by-name/${encodeURIComponent(topologyName)}/nodes/${encodeURIComponent(nodeName)}/webhook-config/subscribe`,
    body,
  )
  return data
}

// Unsubscribe a webhook node. Idempotent on the backend — calling against an
// already-off webhook returns success with `payload.noop = true`.
export async function unsubscribeWebhookConfig(
  topologyName: string,
  nodeName: string,
): Promise<{ status: string; payload: unknown }> {
  const { data } = await api.post<{ status: string; payload: unknown }>(
    `/api/topologies/by-name/${encodeURIComponent(topologyName)}/nodes/${encodeURIComponent(nodeName)}/webhook-config/unsubscribe`,
  )
  return data
}

export async function cascadeWebhookConfigs(
  topologyName: string,
  enable: boolean,
): Promise<CascadeResultItem[]> {
  const { data } = await api.post<{ status: string; items: CascadeResultItem[] }>(
    `/api/topologies/by-name/${encodeURIComponent(topologyName)}/webhook-configs/cascade`,
    { enable },
  )
  return data.items ?? []
}

/**
 * Returns the webhook catalog used by the editor's picker, flattened to one
 * entry per (sdk, application). The endpoint serving this data is
 * `/api/nodes/list/name`, which already returns the standard
 * batch / connector / custom buckets per SDK; we extend it with a `webhook`
 * bucket containing `[{ application, name, logo, events: [...] }]`.
 */
export async function listWebhookCatalog(): Promise<WebhookCatalogEntry[]> {
  const { data } = await api.get<Record<string, Record<string, unknown>>>('/api/nodes/list/name')
  const out: WebhookCatalogEntry[] = []

  for (const [sdkName, buckets] of Object.entries(data ?? {})) {
    const webhookBucket = buckets?.webhook
    if (!Array.isArray(webhookBucket)) continue

    for (const entry of webhookBucket as WebhookCatalogApp[]) {
      if (!entry?.application || !Array.isArray(entry?.events) || entry.events.length === 0) continue
      out.push({ ...entry, sdk: sdkName })
    }
  }

  return out
}

export async function listWebhookEvents(application: string, sdk: string): Promise<WebhookEvent[]> {
  const { data } = await api.get<WebhookEvent[]>(
    `/api/applications/${encodeURIComponent(application)}/webhook-events`,
    { params: { sdk } },
  )
  return Array.isArray(data) ? data : []
}

// Canonical name-based webhook callback URL. Format must match the
// starting-point route `/topologies/{topology}/nodes/{node}/token/{token}/run`
// (see starting-point/pkg/router/routes.go and orchesty-nodejs-sdk's
// TopologyRunner.getWebhookUrl). The earlier `/webhook/topologies/...` prefix
// without the `/run` suffix did not resolve to any backend handler.
export function buildWebhookCallbackUrl(topologyName: string, nodeName: string, token: string): string {
  const baseUrl = (import.meta.env.VITE_STARTING_POINT_URL as string | undefined)
    || (import.meta.env.VITE_BACKEND_URL as string | undefined)
    || ''
  const trimmed = baseUrl.replace(/\/$/, '')
  const safeToken = token || '__missing-token__'
  return `${trimmed}/topologies/${encodeURIComponent(topologyName)}/nodes/${encodeURIComponent(nodeName)}/token/${safeToken}/run`
}
