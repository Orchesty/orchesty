import { BACKEND_URL, STORAGE_KEYS } from '@/config'

/**
 * Process the `cloud_auth` handoff token on page load.
 *
 * Source order (first hit wins):
 *   1. URL fragment  `#cloud_auth=…`  — preferred; the fragment never
 *      reaches the server, so the token can't accidentally land in
 *      ingress / nginx access logs.
 *   2. URL query     `?cloud_auth=…`  — legacy support for in-flight
 *      tokens issued by an older cloud frontend during the rollout
 *      window. Remove this branch one release after the cloud BE forces
 *      the fragment path.
 *
 * Either way we scrub the URL via `replaceState` BEFORE any router /
 * analytics code runs, so the token never makes it into history,
 * SPA breadcrumbs, or Referer headers on subsequent fetches.
 *
 * Returns true if the handoff succeeded and session was established.
 */
export async function handleCloudAuthHandoff(): Promise<boolean> {
  let cloudAuth: string | null = null

  // 1) Fragment-encoded token. `URLSearchParams` parses a `#a=1&b=2`
  //    string identically to a query, so we can reuse the same helper.
  const fragment = window.location.hash?.startsWith('#')
    ? window.location.hash.slice(1)
    : ''
  if (fragment) {
    const fragParams = new URLSearchParams(fragment)
    const tokenFromFragment = fragParams.get('cloud_auth')
    if (tokenFromFragment) {
      cloudAuth = tokenFromFragment
      fragParams.delete('cloud_auth')
      const cleanedFragment = fragParams.toString()
      const cleanUrl =
        window.location.pathname +
        window.location.search +
        (cleanedFragment ? `#${cleanedFragment}` : '')
      window.history.replaceState({}, '', cleanUrl)
    }
  }

  // 2) Query-string fallback (deprecated).
  if (!cloudAuth) {
    const params = new URLSearchParams(window.location.search)
    const tokenFromQuery = params.get('cloud_auth')
    if (tokenFromQuery) {
      cloudAuth = tokenFromQuery
      params.delete('cloud_auth')
      const cleanUrl =
        window.location.pathname +
        (params.toString() ? `?${params}` : '') +
        window.location.hash
      window.history.replaceState({}, '', cleanUrl)
    }
  }

  if (!cloudAuth) return false

  try {
    const res = await fetch(`${BACKEND_URL}/api/cloud/session-handoff`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: cloudAuth }),
    })

    if (!res.ok) {
      // Capture the BE error body so the root cause shows up in DevTools
      // without forcing the user to open the Network tab. Status 503 most
      // commonly means the instance BE could not reach the cloud BE's
      // `/api/internal/handoff/consume` endpoint (dev host port collision
      // is the usual local culprit — see comment in
      // `clients/demo/docker-compose.yml`).
      const bodyText = await res.text().catch(() => '')
      console.error('[CloudAuth] Handoff failed:', res.status, bodyText)
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
