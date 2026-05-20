import { ref, shallowRef, type Ref } from 'vue'
import { TRACE_URL, STORAGE_KEYS } from '@/config'

export type TraceConnectionStatus = 'idle' | 'connecting' | 'open' | 'reconnecting' | 'closed' | 'error'

export interface TraceResponseData {
  content: string
}

export interface TraceErrorData {
  code: number
  message: string
}

/**
 * Payload of a `quota_exceeded` server frame. Mirrors the Go-side
 * `QuotaData` struct, which itself round-trips the PHP
 * `QuotaExceededException::toPayload()` body. All fields default to safe
 * zero values so the FE can render a generic info card even if any field
 * is missing.
 */
export interface TraceQuotaExceededData {
  limit: number
  used: number
  resetAt: string
}

interface ServerMessage {
  type: 'response' | 'error' | 'quota_exceeded' | string
  data: unknown
}

interface ClientMessage {
  type: 'token' | 'request'
  data: unknown
}

interface UseTraceSocketOptions {
  /** Initial reconnect backoff in ms. Defaults to 1000. */
  initialBackoffMs?: number
  /** Maximum reconnect backoff in ms. Defaults to 30000. */
  maxBackoffMs?: number
  /** Disable automatic reconnect (useful in tests). */
  noReconnect?: boolean
  /**
   * How often the open socket re-sends a fresh access token via a `{type: "token"}`
   * frame so the backend session never works with an expired Auth0 access token.
   * Defaults to 5 minutes — well below the typical 1h Auth0 lifetime.
   */
  tokenRefreshIntervalMs?: number
}

export interface UseTraceSocketReturn {
  status: Ref<TraceConnectionStatus>
  lastError: Ref<TraceErrorData | null>
  connect: (userID: string) => void
  disconnect: () => void
  // Optional `extraContext` mirrors the Go-side RequestData.ExtraContext —
  // a small whitelist-validated key/value bag the FE can use to surface
  // client-side state (e.g. onboardingStage) so the LLM can disambiguate
  // ambiguous prompts ("what's next") without a separate stateful service.
  send: (content: string, extraContext?: Record<string, string>) => void
  onResponse: (cb: (data: TraceResponseData) => void) => void
  onError: (cb: (data: TraceErrorData) => void) => void
  onQuotaExceeded: (cb: (data: TraceQuotaExceededData) => void) => void
}

/**
 * WebSocket client for the Trace Go service.
 *
 * Auth flow: the browser cannot send custom headers on a WebSocket handshake, so the
 * authentication token is sent as the first {type: "token"} frame after `onopen`. The
 * server validates it via /api/user/check_logged and matches the user against the
 * `?user=` query parameter.
 *
 * Tokens are read from localStorage. On socket close caused by an auth error or
 * after the periodic refresh tick, the token is rotated through one of two paths:
 *
 *   - Cloud-handoff session: refresh via `/api/user/check_logged` on the instance
 *     backend, which mints a fresh JWT for the current handoff identity. We
 *     never call Auth0 from a cloud instance origin — the SDK isn't installed
 *     in cloud mode and a silent renewal from the instance host would hit the
 *     Auth0 callback-mismatch wall.
 *   - Standalone (Auth0 active): `auth0Plugin.getAccessTokenSilently()` as before.
 *
 * Outbound messages sent before the socket is open are queued and flushed once
 * authentication completes.
 */
export function useTraceSocket(options: UseTraceSocketOptions = {}): UseTraceSocketReturn {
  const initialBackoff = options.initialBackoffMs ?? 1000
  const maxBackoff = options.maxBackoffMs ?? 30_000
  const tokenRefreshIntervalMs = options.tokenRefreshIntervalMs ?? 5 * 60 * 1000

  const status = ref<TraceConnectionStatus>('idle')
  const lastError = shallowRef<TraceErrorData | null>(null)

  const responseListeners = new Set<(data: TraceResponseData) => void>()
  const errorListeners = new Set<(data: TraceErrorData) => void>()
  const quotaListeners = new Set<(data: TraceQuotaExceededData) => void>()

  let socket: WebSocket | null = null
  let userID = ''
  let backoffMs = initialBackoff
  let reconnectTimer: ReturnType<typeof setTimeout> | null = null
  let tokenRefreshTimer: ReturnType<typeof setInterval> | null = null
  let inFlightRotation: Promise<boolean> | null = null
  let manualClose = false
  let pendingMessages: ClientMessage[] = []
  let authenticated = false

  const buildUrl = (id: string): string => {
    const base = TRACE_URL.replace(/\/$/, '')
    return `${base}/trace?user=${encodeURIComponent(id)}`
  }

  const readToken = (): string | null => {
    return localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN)
  }

  const refreshTokenSilently = async (): Promise<string | null> => {
    try {
      // Cloud-handoff session: refresh via the instance backend. The Auth0
      // SDK is NOT installed in cloud mode (see auth/auth0-plugin.ts) and
      // calling getAccessTokenSilently() would either throw or attempt a
      // silent renewal iframe on the instance origin — both wrong.
      const hasCloudHandoff = localStorage.getItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION) === 'true'
      if (hasCloudHandoff) {
        const { default: api } = await import('@/services/api')
        const response = await api.get('/api/user/check_logged')
        const newToken = response?.data?.token
        if (typeof newToken === 'string' && newToken.length > 0) {
          localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, newToken)
          return newToken
        }
        return null
      }

      const { getAuth0Plugin, isAuth0Active } = await import('@/auth/auth0-plugin')
      if (!isAuth0Active()) return null
      const plugin = getAuth0Plugin()
      if (!plugin) return null
      const newToken = await plugin.getAccessTokenSilently()
      if (newToken) {
        localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, newToken)
        return newToken
      }
      return null
    } catch {
      return null
    }
  }

  const sendRaw = (msg: ClientMessage): boolean => {
    if (socket && socket.readyState === WebSocket.OPEN) {
      socket.send(JSON.stringify(msg))
      return true
    }
    return false
  }

  const flushPending = () => {
    if (!pendingMessages.length) return
    const queue = pendingMessages
    pendingMessages = []
    for (const msg of queue) {
      if (!sendRaw(msg)) {
        pendingMessages.push(msg)
      }
    }
  }

  // Rotate the access token over the open socket: ask Auth0 for a fresh token
  // (silent refresh from the SDK's local cache when still valid, otherwise a
  // network round-trip), persist it, and emit a `{type:"token"}` frame so the
  // Go backend swaps `sess.token` for downstream MCP / AI calls. Concurrent
  // calls coalesce on a single in-flight promise so a periodic tick and a
  // 401-error-frame reaction never duplicate the work.
  const rotateToken = (): Promise<boolean> => {
    if (inFlightRotation) return inFlightRotation

    inFlightRotation = (async () => {
      const fresh = (await refreshTokenSilently()) ?? readToken()
      if (!fresh) return false
      if (!socket || socket.readyState !== WebSocket.OPEN) return false
      return sendRaw({ type: 'token', data: { token: fresh } })
    })().finally(() => {
      inFlightRotation = null
    })

    return inFlightRotation
  }

  const stopTokenRefreshTimer = () => {
    if (tokenRefreshTimer) {
      clearInterval(tokenRefreshTimer)
      tokenRefreshTimer = null
    }
  }

  const startTokenRefreshTimer = () => {
    stopTokenRefreshTimer()
    if (tokenRefreshIntervalMs <= 0) return
    tokenRefreshTimer = setInterval(() => {
      if (!socket || socket.readyState !== WebSocket.OPEN) return
      void rotateToken()
    }, tokenRefreshIntervalMs)
  }

  const scheduleReconnect = async (reason: 'unauth' | 'transport' | 'unknown') => {
    if (manualClose || options.noReconnect) {
      status.value = 'closed'
      return
    }

    status.value = 'reconnecting'

    if (reason === 'unauth') {
      // Try to refresh the access token before backing off.
      await refreshTokenSilently()
    }

    if (reconnectTimer) clearTimeout(reconnectTimer)
    reconnectTimer = setTimeout(() => {
      open()
    }, backoffMs)

    backoffMs = Math.min(backoffMs * 2, maxBackoff)
  }

  const open = () => {
    if (!userID) return
    if (!TRACE_URL) {
      lastError.value = { code: 0, message: 'VITE_TRACE_URL is not configured' }
      status.value = 'error'
      return
    }

    cleanupSocket()
    authenticated = false
    status.value = 'connecting'

    try {
      socket = new WebSocket(buildUrl(userID))
    } catch (err) {
      lastError.value = { code: 0, message: (err as Error)?.message || 'failed to construct WebSocket' }
      status.value = 'error'
      void scheduleReconnect('transport')
      return
    }

    socket.onopen = () => {
      const token = readToken()
      if (!token) {
        lastError.value = { code: 401, message: 'no auth token in localStorage' }
        manualClose = false
        socket?.close()
        return
      }
      // Reset backoff on successful TCP open; will be restored if auth fails.
      backoffMs = initialBackoff
      sendRaw({ type: 'token', data: { token } })
      // We treat the connection as "open" once the WS is open. The server will close
      // it shortly with an auth error if the token is invalid; the close handler then
      // triggers a refresh + reconnect.
      status.value = 'open'
      authenticated = true
      flushPending()
      // Keep the backend's `sess.token` fresh for the lifetime of this socket
      // so long-running chats don't start failing with 401s once the original
      // Auth0 access token expires (~1h).
      startTokenRefreshTimer()
    }

    socket.onmessage = (event) => {
      let parsed: ServerMessage
      try {
        parsed = JSON.parse(typeof event.data === 'string' ? event.data : '')
      } catch {
        return
      }

      if (parsed.type === 'response') {
        const data = parsed.data as TraceResponseData
        responseListeners.forEach((cb) => cb(data))
      } else if (parsed.type === 'quota_exceeded') {
        const raw = (parsed.data ?? {}) as Partial<TraceQuotaExceededData>
        const data: TraceQuotaExceededData = {
          limit: typeof raw.limit === 'number' ? raw.limit : 0,
          used: typeof raw.used === 'number' ? raw.used : 0,
          resetAt: typeof raw.resetAt === 'string' ? raw.resetAt : '',
        }
        quotaListeners.forEach((cb) => cb(data))
      } else if (parsed.type === 'error') {
        const data = parsed.data as TraceErrorData
        lastError.value = data
        errorListeners.forEach((cb) => cb(data))

        // Reactive safety net: backend now relays upstream 401/403 over the
        // open socket (instead of dropping the connection). Rotate the token
        // in place so the next user message picks up a fresh `sess.token`. If
        // we cannot obtain a new token, fall back to a full reconnect cycle
        // — that still tries `refreshTokenSilently()` before reopening.
        if (data.code === 401 || data.code === 403) {
          void rotateToken().then((ok) => {
            if (!ok) {
              cleanupSocket()
              void scheduleReconnect('unauth')
            }
          })
        }
      }
    }

    socket.onerror = () => {
      // onerror fires before onclose; rely on onclose for reconnection logic.
      status.value = 'error'
    }

    socket.onclose = (event) => {
      const isAuthFailure =
        lastError.value?.code === 401 ||
        lastError.value?.code === 403 ||
        event.code === 1008 ||
        event.code === 4001

      authenticated = false
      socket = null
      stopTokenRefreshTimer()

      if (manualClose) {
        status.value = 'closed'
        return
      }

      void scheduleReconnect(isAuthFailure ? 'unauth' : 'transport')
    }
  }

  const cleanupSocket = () => {
    stopTokenRefreshTimer()
    if (!socket) return
    socket.onopen = null
    socket.onmessage = null
    socket.onerror = null
    socket.onclose = null
    if (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING) {
      socket.close()
    }
    socket = null
  }

  const connect = (id: string) => {
    userID = id
    manualClose = false
    backoffMs = initialBackoff
    open()
  }

  const disconnect = () => {
    manualClose = true
    if (reconnectTimer) {
      clearTimeout(reconnectTimer)
      reconnectTimer = null
    }
    cleanupSocket()
    status.value = 'closed'
  }

  const send = (content: string, extraContext?: Record<string, string>) => {
    const data: { content: string; extraContext?: Record<string, string> } = { content }
    if (extraContext && Object.keys(extraContext).length > 0) {
      data.extraContext = extraContext
    }
    const msg: ClientMessage = { type: 'request', data }
    if (!authenticated || !sendRaw(msg)) {
      pendingMessages.push(msg)
    }
  }

  const onResponse = (cb: (data: TraceResponseData) => void) => {
    responseListeners.add(cb)
  }

  const onError = (cb: (data: TraceErrorData) => void) => {
    errorListeners.add(cb)
  }

  const onQuotaExceeded = (cb: (data: TraceQuotaExceededData) => void) => {
    quotaListeners.add(cb)
  }

  return { status, lastError, connect, disconnect, send, onResponse, onError, onQuotaExceeded }
}
