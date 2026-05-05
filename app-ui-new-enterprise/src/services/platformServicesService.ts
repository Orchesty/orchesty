import api from './api'

export interface PlatformServiceBinding {
  id: string
  serviceType: string
  applicationKey: string
  sdk: string | null
  user: string
}

interface SetBindingPayload {
  applicationKey: string
  sdk: string
}

/**
 * Fetch all platform service bindings.
 */
export async function fetchBindings(): Promise<PlatformServiceBinding[]> {
  const response = await api.get<PlatformServiceBinding[]>('/api/platform-services')
  return response.data ?? []
}

/**
 * Find binding for a given service type, or null when not configured.
 */
export async function findBinding(serviceType: string): Promise<PlatformServiceBinding | null> {
  const bindings = await fetchBindings()
  return bindings.find((b) => b.serviceType === serviceType) ?? null
}

/**
 * Persist (upsert) a binding for the given service type.
 * Both `applicationKey` and `sdk` are required by the backend contract.
 */
export async function setBinding(
  serviceType: string,
  applicationKey: string,
  sdk: string,
): Promise<PlatformServiceBinding> {
  const payload: SetBindingPayload = { applicationKey, sdk }
  const response = await api.put<PlatformServiceBinding>(
    `/api/platform-services/${encodeURIComponent(serviceType)}`,
    payload,
  )
  return response.data
}

/**
 * Remove a binding for the given service type.
 */
export async function removeBinding(serviceType: string): Promise<void> {
  await api.delete(`/api/platform-services/${encodeURIComponent(serviceType)}`)
}

/**
 * Trace cloud-relay quota status returned by
 * `GET /platform-services/trace-ai-provider/quota`. Drives the
 * mode-aware Settings/TraceTab UI:
 *
 *   - mode = "user"     : user has installed their own LLM and bound it as
 *                         `trace-ai-provider`. Cap is not enforced.
 *   - mode = "system"   : no user binding, Trace feature flag enabled.
 *                         Default LLM via cloud-relay applies, `used`/`limit`
 *                         render the live badge.
 *   - mode = "disabled" : Trace feature flag is off. UI should hide the tab.
 */
export type TraceQuotaMode = 'user' | 'system' | 'disabled'

export interface TraceQuotaStatus {
  mode: TraceQuotaMode
  used: number
  limit: number
  resetAt: string
}

export async function fetchTraceQuota(): Promise<TraceQuotaStatus> {
  const response = await api.get<TraceQuotaStatus>('/api/platform-services/trace-ai-provider/quota')
  return response.data
}

