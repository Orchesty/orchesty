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

interface ServerMessage {
  type: 'response' | 'error' | string
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
}

export interface UseTraceSocketReturn {
  status: Ref<TraceConnectionStatus>
  lastError: Ref<TraceErrorData | null>
  connect: (userID: string) => void
  disconnect: () => void
  send: (content: string) => void
  onResponse: (cb: (data: TraceResponseData) => void) => void
  onError: (cb: (data: TraceErrorData) => void) => void
}

/**
 * WebSocket client for the Trace Go service.
 *
 * Auth flow: the browser cannot send custom headers on a WebSocket handshake, so the
 * authentication token is sent as the first {type: "token"} frame after `onopen`. The
 * server validates it via /api/user/check_logged and matches the user against the
 * `?user=` query parameter.
 *
 * Tokens are read from localStorage (auth0 access token). On socket close caused by
 * an auth error, we attempt to refresh the token via `auth0Plugin.getAccessTokenSilently()`
 * before reconnecting.
 *
 * Outbound messages sent before the socket is open are queued and flushed once
 * authentication completes.
 */
export function useTraceSocket(options: UseTraceSocketOptions = {}): UseTraceSocketReturn {
  const initialBackoff = options.initialBackoffMs ?? 1000
  const maxBackoff = options.maxBackoffMs ?? 30_000

  const status = ref<TraceConnectionStatus>('idle')
  const lastError = shallowRef<TraceErrorData | null>(null)

  const responseListeners = new Set<(data: TraceResponseData) => void>()
  const errorListeners = new Set<(data: TraceErrorData) => void>()

  let socket: WebSocket | null = null
  let userID = ''
  let backoffMs = initialBackoff
  let reconnectTimer: ReturnType<typeof setTimeout> | null = null
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
      const { auth0Plugin } = await import('@/auth/auth0-plugin')
      if (!auth0Plugin) return null
      const newToken = await auth0Plugin.getAccessTokenSilently()
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
      } else if (parsed.type === 'error') {
        const data = parsed.data as TraceErrorData
        lastError.value = data
        errorListeners.forEach((cb) => cb(data))
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

      if (manualClose) {
        status.value = 'closed'
        return
      }

      void scheduleReconnect(isAuthFailure ? 'unauth' : 'transport')
    }
  }

  const cleanupSocket = () => {
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

  const send = (content: string) => {
    const msg: ClientMessage = { type: 'request', data: { content } }
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

  return { status, lastError, connect, disconnect, send, onResponse, onError }
}
