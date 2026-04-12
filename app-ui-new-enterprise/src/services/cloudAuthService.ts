import { BACKEND_URL, STORAGE_KEYS } from '@/config'

/**
 * Process the `cloud_auth` query parameter on page load.
 * Sends the HMAC-signed handoff token to the instance backend,
 * which validates it and returns a legacy JWT session.
 *
 * Returns true if the handoff succeeded and session was established.
 */
export async function handleCloudAuthHandoff(): Promise<boolean> {
  const params = new URLSearchParams(window.location.search)
  const cloudAuth = params.get('cloud_auth')
  if (!cloudAuth) return false

  params.delete('cloud_auth')
  const cleanUrl =
    window.location.pathname + (params.toString() ? `?${params}` : '') + window.location.hash
  window.history.replaceState({}, '', cleanUrl)

  try {
    const res = await fetch(`${BACKEND_URL}/api/cloud/session-handoff`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: cloudAuth }),
    })

    if (!res.ok) {
      console.error('[CloudAuth] Handoff failed:', res.status)
      sessionStorage.setItem(STORAGE_KEYS.CLOUD_HANDOFF_FAILED, 'true')
      return false
    }

    const data = await res.json()
    if (data.token) {
      localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, data.token)
      localStorage.setItem(
        STORAGE_KEYS.AUTH_USER,
        JSON.stringify({ id: data.id, email: data.email, picture: data.picture || undefined, isOrgMember: data.isOrgMember === true, settings: data.settings || {} }),
      )
      localStorage.setItem(STORAGE_KEYS.LAST_TOKEN_REFRESH, String(Date.now()))
      localStorage.setItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION, 'true')
      sessionStorage.removeItem(STORAGE_KEYS.CLOUD_HANDOFF_FAILED)
      return true
    }
  } catch (err) {
    console.error('[CloudAuth] Handoff error:', err)
  }
  sessionStorage.setItem(STORAGE_KEYS.CLOUD_HANDOFF_FAILED, 'true')
  return false
}
