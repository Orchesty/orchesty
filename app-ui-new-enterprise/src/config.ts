function env(value: string | undefined, placeholder: string): string {
  return value || (import.meta.env.PROD ? placeholder : '')
}

export const BACKEND_URL = env(import.meta.env.VITE_BACKEND_URL, '%VITE_BACKEND_URL%')
export const STARTING_POINT_URL = env(import.meta.env.VITE_STARTING_POINT_URL, '%VITE_STARTING_POINT_URL%')
export const NOTIFIER_URL = env(import.meta.env.VITE_NOTIFIER_URL, '%VITE_NOTIFIER_URL%')
export const TRACE_URL = env(import.meta.env.VITE_TRACE_URL, '%VITE_TRACE_URL%')
export const TITLE = env(import.meta.env.VITE_TITLE, '%VITE_TITLE%')
export const AUTH0_DOMAIN = env(import.meta.env.VITE_AUTH0_DOMAIN, '%VITE_AUTH0_DOMAIN%')
export const AUTH0_CLIENT_ID = env(import.meta.env.VITE_AUTH0_CLIENT_ID, '%VITE_AUTH0_CLIENT_ID%')
export const AUTH0_AUDIENCE = env(import.meta.env.VITE_AUTH0_AUDIENCE, '%VITE_AUTH0_AUDIENCE%')

export const STORAGE_KEYS = {
  AUTH_TOKEN: 'auth_token',
  AUTH_USER: 'auth_user',
  LAST_TOKEN_REFRESH: 'lastTokenRefreshTime',
  CLOUD_HANDOFF_SESSION: 'cloud_handoff_session',
  CLOUD_HANDOFF_FAILED: 'cloud_handoff_failed',
  // sessionStorage — anti-loop guard. Set whenever the router decides to
  // redirect the user back to the cloud sign-in. The cloud sign-in is the
  // ONLY UI that should be allowed to bounce us back here; if it does so
  // within the loop window, we surface the error in the local
  // /auth-error view instead of bouncing again, which would otherwise
  // lock the user into an infinite redirect cycle when the instance↔cloud
  // handoff is misconfigured (e.g. cloud BE unreachable from the pod).
  CLOUD_BOUNCE_AT: 'cloud_bounce_at',
  CLOUD_BOUNCE_COUNT: 'cloud_bounce_count',
  PENDING_INVITE_TOKEN: 'pending_invite_token',
  AUTH0_LOGIN_FAILED: 'auth0_login_failed',
  TRACE_HISTORY: 'trace_history',
  // sessionStorage key — Trace onboarding stage memory, scoped to the
  // current tab. Cleared when the user closes the tab so each new session
  // is a clean slate (the LLM still has the chat history from
  // localStorage, but the explicit "where you are in the wizard" hint
  // resets).
  TRACE_ONBOARDING_STAGE: 'trace_onboarding_stage',
} as const
