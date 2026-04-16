import { ref, onMounted, onUnmounted } from 'vue'
import { NOTIFIER_URL } from '@/config'

export interface InAppNotification {
  id: string
  tenant_id: string
  event_type: string
  severity: string
  message: string
  topology_id?: string | null
  topology_name?: string | null
  node_name?: string | null
  created_at: string
}

export function useNotificationStream() {
  const latestNotification = ref<InAppNotification | null>(null)
  const unreadCount = ref(0)
  const connected = ref(false)

  let eventSource: EventSource | null = null
  const listeners: Array<(n: InAppNotification) => void> = []

  const connect = () => {
    const url = `${NOTIFIER_URL}/api/notifications/stream`

    eventSource = new EventSource(url)

    eventSource.onopen = () => {
      connected.value = true
    }

    eventSource.onmessage = (event) => {
      try {
        const notification: InAppNotification = JSON.parse(event.data)
        latestNotification.value = notification
        unreadCount.value++
        listeners.forEach((cb) => cb(notification))
      } catch (err) {
        console.error('[SSE] Failed to parse notification:', err)
      }
    }

    eventSource.onerror = () => {
      connected.value = false
    }
  }

  const disconnect = () => {
    if (eventSource) {
      eventSource.close()
      eventSource = null
      connected.value = false
    }
  }

  const onNotification = (callback: (n: InAppNotification) => void) => {
    listeners.push(callback)
  }

  const resetUnreadCount = () => {
    unreadCount.value = 0
  }

  onMounted(connect)
  onUnmounted(disconnect)

  return {
    latestNotification,
    unreadCount,
    connected,
    onNotification,
    resetUnreadCount,
  }
}
