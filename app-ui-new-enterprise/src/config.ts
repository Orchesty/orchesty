export const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.66:8085'
export const TITLE = import.meta.env.VITE_TITLE || ''

export const STORAGE_KEYS = {
  AUTH_TOKEN: 'auth_token',
  AUTH_USER: 'auth_user',
  LAST_TOKEN_REFRESH: 'lastTokenRefreshTime',
  CLOUD_HANDOFF_SESSION: 'cloud_handoff_session',
  CLOUD_HANDOFF_FAILED: 'cloud_handoff_failed',
  PENDING_INVITE_TOKEN: 'pending_invite_token',
  AUTH0_LOGIN_FAILED: 'auth0_login_failed',
} as const
