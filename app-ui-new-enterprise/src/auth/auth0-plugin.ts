import { createAuth0 } from '@auth0/auth0-vue'
import { AUTH0_DOMAIN, AUTH0_CLIENT_ID, AUTH0_AUDIENCE } from '@/config'

// Auth0 lazy factory + cloud-mode guard.
//
// Why this is wrapped in a factory instead of a top-level constant:
// In cloud mode the instance UI must NEVER initialize the Auth0 SDK on its
// own origin (`window.location.origin === https://ui-<x>.<region>.cloud.orchesty.io`).
// All authentication originates from the cloud frontend at
// `https://cloud.orchesty.io` and arrives here as a session-handoff token.
// If we created the Auth0 client on the instance origin, the SDK would:
//   1) Use `redirect_uri = window.location.origin`, which is NOT registered
//      in the central Auth0 Application Allowed Callback URLs (the list is
//      pinned to `cloud.orchesty.io` for security; per-instance wildcards
//      would let any subdomain hijack the callback) → Auth0 would reject
//      the callback with "callback URL mismatch".
//   2) Periodically run silent token renewal via an iframe on the instance
//      origin, hitting the same mismatch.
//
// Both failure modes have been observed in production. The fix is to never
// instantiate Auth0 on a cloud-mode instance. `cloudMode` is fetched from
// `/api/status` at boot in `main.ts`, then `buildAuth0Plugin(cloudMode)` is
// called once with the final value. In cloud mode it returns `null` and
// the rest of the SPA falls back to handoff-only auth (see
// `services/cloudAuthService.ts` and the cloud guard in `router/index.ts`).
//
// Standalone (non-cloud) deployments still use Auth0 normally — that's the
// only branch where this factory returns a real plugin.

const domain = AUTH0_DOMAIN
const clientId = AUTH0_CLIENT_ID
const audience = AUTH0_AUDIENCE

let pluginInstance: ReturnType<typeof createAuth0> | null = null
let active = false

export function buildAuth0Plugin(cloudMode: boolean): ReturnType<typeof createAuth0> | null {
  // Cloud-mode instances never own an Auth0 client.
  if (cloudMode) {
    pluginInstance = null
    active = false
    return null
  }
  // Standalone instances need both ENVs to make Auth0 work at all.
  if (!domain || !clientId) {
    pluginInstance = null
    active = false
    return null
  }
  pluginInstance = createAuth0({
    domain,
    clientId,
    authorizationParams: {
      redirect_uri: window.location.origin,
      ...(audience && { audience }),
    },
    cacheLocation: 'localstorage',
  })
  active = true
  return pluginInstance
}

// Runtime accessor for the installed plugin. Returns `null` in cloud mode
// (or when Auth0 ENVs are absent). Callers MUST handle null — never assume
// the plugin is available just because the ENVs are set.
export function getAuth0Plugin(): ReturnType<typeof createAuth0> | null {
  return pluginInstance
}

// Runtime flag: did we actually install Auth0 on this boot? In cloud mode
// this is ALWAYS false, even if the ENVs are present. All runtime decisions
// (router guards, useTraceSocket, auth store logout, api.ts interceptor)
// MUST check `isAuth0Active()` instead of the legacy `isAuth0Enabled` flag.
export function isAuth0Active(): boolean {
  return active
}

// ENV-only flag, kept for backwards compatibility with callsites that pre-
// date the cloud-mode split. Whenever a callsite is about to call into the
// Auth0 SDK (loginWithRedirect, getAccessTokenSilently, logout), prefer
// `isAuth0Active()` so cloud-mode instances are correctly excluded.
export const isAuth0Enabled = !!(domain && clientId)
