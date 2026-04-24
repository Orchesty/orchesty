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
