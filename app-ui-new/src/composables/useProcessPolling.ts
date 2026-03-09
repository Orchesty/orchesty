import { ref, onBeforeUnmount } from 'vue'
import type { RawNodeMetrics } from '@/services/topologyMetricsService'
import { fetchRawTopologyMetrics } from '@/services/topologyMetricsService'
import { fetchLatestProcess } from '@/services/processesService'
import { fetchTrashItems } from '@/services/trashService'
import type { Process } from '@/types/processes'
import {
  PROCESS_POLL_FAST_INTERVAL_MS,
  PROCESS_POLL_FAST_COUNT,
  PROCESS_POLL_SLOW_INTERVAL_MS,
  PROCESS_POLL_MAX_DURATION_MS,
} from '@/config/topology'

/**
 * Composable for polling process metrics and status after a topology run.
 *
 * Progressive interval: first N ticks are fast (2s), then slow (10s).
 * Stops automatically when the process completes/fails or safety timeout is reached.
 *
 * On failure, fetches failed messages (trash) to identify which nodes had errors.
 */
export function useProcessPolling(topologyId: string) {
  const isPolling = ref(false)
  const rawMetrics = ref<RawNodeMetrics | null>(null)
  const latestProcess = ref<Process | null>(null)
  const failedNodeIds = ref<string[]>([])
  const processCompleted = ref(false)

  let timeoutId: ReturnType<typeof setTimeout> | null = null
  let safetyTimeoutId: ReturnType<typeof setTimeout> | null = null
  let tickCount = 0
  let pollingStartedAt = 0

  const getInterval = (): number => {
    return tickCount < PROCESS_POLL_FAST_COUNT
      ? PROCESS_POLL_FAST_INTERVAL_MS
      : PROCESS_POLL_SLOW_INTERVAL_MS
  }

  /**
   * Check if a process was started around the time polling began
   * (allow 30s of clock skew / backend delay).
   */
  const isRecentProcess = (process: Process): boolean => {
    const processStart = new Date(process.startTime).getTime()
    return processStart >= pollingStartedAt - 30_000
  }

  const poll = async () => {
    if (!isPolling.value) return

    try {
      const [metrics, process] = await Promise.all([
        fetchRawTopologyMetrics(topologyId),
        fetchLatestProcess(topologyId),
      ])

      rawMetrics.value = metrics
      latestProcess.value = process

      if (process && isRecentProcess(process)) {
        if (process.status === 'completed' || process.status === 'failed') {
          if (process.status === 'failed') {
            try {
              const trash = await fetchTrashItems({
                correlationId: process.id,
                perPage: 1000,
              })
              const uniqueIds = [...new Set(trash.data.map(item => item.nodeId))]
              failedNodeIds.value = uniqueIds
            } catch (err) {
              console.error('Failed to fetch failed messages:', err)
            }
          }
          processCompleted.value = true
          stopPolling()
          return
        }
      }
    } catch (err) {
      console.error('Process polling error:', err)
    }

    tickCount++
    if (isPolling.value) {
      timeoutId = setTimeout(poll, getInterval())
    }
  }

  const startPolling = () => {
    stopPolling()
    isPolling.value = true
    processCompleted.value = false
    failedNodeIds.value = []
    latestProcess.value = null
    rawMetrics.value = null
    tickCount = 0
    pollingStartedAt = Date.now()

    timeoutId = setTimeout(poll, PROCESS_POLL_FAST_INTERVAL_MS)

    safetyTimeoutId = setTimeout(() => {
      if (isPolling.value) {
        console.warn('Process polling safety timeout reached')
        stopPolling()
      }
    }, PROCESS_POLL_MAX_DURATION_MS)
  }

  const stopPolling = () => {
    isPolling.value = false
    if (timeoutId) {
      clearTimeout(timeoutId)
      timeoutId = null
    }
    if (safetyTimeoutId) {
      clearTimeout(safetyTimeoutId)
      safetyTimeoutId = null
    }
  }

  const resetToFastPolling = () => {
    if (!isPolling.value) return
    tickCount = 0
    if (timeoutId) {
      clearTimeout(timeoutId)
      timeoutId = null
    }
    timeoutId = setTimeout(poll, PROCESS_POLL_FAST_INTERVAL_MS)
  }

  onBeforeUnmount(() => {
    stopPolling()
  })

  return {
    isPolling,
    rawMetrics,
    latestProcess,
    failedNodeIds,
    processCompleted,
    startPolling,
    stopPolling,
    resetToFastPolling,
  }
}
