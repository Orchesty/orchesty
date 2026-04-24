/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** Backend API base URL */
  readonly VITE_BACKEND_URL: string
  /** Trace WebSocket service base URL (e.g. wss://trace.cloud.orchesty.io) */
  readonly VITE_TRACE_URL: string
  /** Auth0 tenant domain (e.g. dev-xxx.eu.auth0.com) */
  readonly VITE_AUTH0_DOMAIN: string
  /** Auth0 application Client ID */
  readonly VITE_AUTH0_CLIENT_ID: string
  /** Auth0 API audience identifier */
  readonly VITE_AUTH0_AUDIENCE: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
