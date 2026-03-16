import { ref, onBeforeUnmount } from 'vue'
import type { ProcessDetail } from '@/services/processDetailService'
import { fetchProcessDetail } from '@/services/processDetailService'
import { fetchLatestProcess } from '@/services/processesService'
import type { Process } from '@/types/processes'
import {
  PROCESS_POLL_FAST_INTERVAL_MS,
  PROCESS_POLL_FAST_COUNT,
  PROCESS_POLL_SLOW_INTERVAL_MS,
  PROCESS_POLL_MAX_DURATION_MS,
} from '@/config/topology'

function mapApiStatus(status: string): 'running' | 'completed' | 'failed' {
  if (status === 'COMPLETED') return 'completed'
  if (status === 'FAILED') return 'failed'
  return 'running'
}

/**
 * Composable for polling process detail after a topology run.
 *
 * Progressive interval: first N ticks are fast (2s), then slow (10s).
 * Stops automatically when the process completes/fails or safety timeout is reached.
 *
 * Phase 1 (discovery): polls fetchLatestProcess until a recent correlation ID appears.
 * Phase 2 (detail):    polls fetchProcessDetail for the full node-level data.
 */
export function useProcessPolling(topologyId: string) {
  const isPolling = ref(false)
  const processDetail = ref<ProcessDetail | null>(null)
  const processCompleted = ref(false)

  let timeoutId: ReturnType<typeof setTimeout> | null = null
  let safetyTimeoutId: ReturnType<typeof setTimeout> | null = null
  let tickCount = 0
  let pollingStartedAt = 0
  let correlationId: string | null = null

  const getInterval = (): number => {
    return tickCount < PROCESS_POLL_FAST_COUNT
      ? PROCESS_POLL_FAST_INTERVAL_MS
      : PROCESS_POLL_SLOW_INTERVAL_MS
  }

  const isRecentProcess = (process: Process): boolean => {
    const processStart = new Date(process.startTime).getTime()
    return processStart >= pollingStartedAt - 30_000
  }

  const poll = async () => {
    if (!isPolling.value) return

    try {
      if (!correlationId) {
        const process = await fetchLatestProcess(topologyId)
        if (process && isRecentProcess(process)) {
          correlationId = process.id
        }
      }

      if (correlationId) {
        const detail = await fetchProcessDetail(correlationId)
        processDetail.value = detail

        const status = mapApiStatus(detail.status)
        if (status === 'completed' || status === 'failed') {
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
    processDetail.value = null
    correlationId = null
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

  /**
   * Start polling with a known correlation ID (skips discovery phase).
   * First tick runs immediately.
   */
  const startPollingWithId = (id: string) => {
    stopPolling()
    isPolling.value = true
    processCompleted.value = false
    processDetail.value = null
    correlationId = id
    tickCount = 0
    pollingStartedAt = 0

    poll()

    safetyTimeoutId = setTimeout(() => {
      if (isPolling.value) {
        console.warn('Process polling safety timeout reached')
        stopPolling()
      }
    }, PROCESS_POLL_MAX_DURATION_MS)
  }

  /**
   * One-shot fetch for a known correlation ID (no polling).
   * Used for initial page load to populate overlays.
   */
  const fetchOnce = async (id: string): Promise<ProcessDetail | null> => {
    try {
      const detail = await fetchProcessDetail(id)
      processDetail.value = detail
      return detail
    } catch (err) {
      console.error('Failed to fetch process detail:', err)
      return null
    }
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
    processDetail,
    processCompleted,
    startPolling,
    startPollingWithId,
    fetchOnce,
    stopPolling,
    resetToFastPolling,
  }
}
