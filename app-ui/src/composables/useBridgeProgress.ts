import { ref, computed, onBeforeUnmount } from 'vue'
import { BRIDGE_WARMUP_MS, BRIDGE_PROGRESS_TICK_MS } from '@/config/topology'

/**
 * U+2007 FIGURE SPACE - as wide as a digit and, unlike an ASCII space, not
 * collapsed by HTML whitespace handling.
 *
 * Built from its codepoint rather than typed literally: as a literal it is an
 * invisible character that editors and formatters can silently normalise into an
 * ASCII space, which would break the alignment while leaving no visible diff.
 */
const FIGURE_SPACE = String.fromCharCode(0x2007)

/**
 * Fixed-width percentage, so the label never changes size as the number grows
 * from one to three digits. Pair with `tabular-nums` on the rendering element.
 * Exported for components that receive the raw percentage as a prop.
 */
export function formatProgressLabel(percent: number): string {
  return `${String(percent).padStart(3, FIGURE_SPACE)}%`
}

/**
 * Optimistic progress for bridge warm-up (publish / republish).
 *
 * The backend returns as soon as Kubernetes accepts the Deployment, long before
 * the bridge pod has started, connected to Mongo/Rabbit and declared its queues.
 * There is no readiness endpoint to poll, so we hold the promise for a fixed
 * window and animate a bar across it.
 *
 * Semantics: `Promise.all([task, sleep(BRIDGE_WARMUP_MS)])`.
 * - Task finishes early  -> we still wait out the window.
 * - Task runs long       -> the bar clamps at 99% until it settles.
 * - Task rejects         -> the countdown aborts immediately and the error is
 *                           rethrown, so fast failures (e.g. the 409 slot-limit
 *                           guard) surface without a pointless 15s wait.
 *
 * The percentage is derived from elapsed wall-clock time rather than a tick
 * counter: background tabs throttle setInterval to ~1s, and timestamp maths
 * stays correct either way.
 *
 * Overlapping runs are safe. Each run owns its own timers and carries a
 * monotonic id, so only the newest run writes to the shared refs. Crucially, a
 * warm-up timeout is never cancelled by anything - cancelling one would leave
 * that run's `Promise.all` unsettled forever, hanging whoever awaited it.
 */
export function useBridgeProgress() {
  const progress = ref(0)
  const isRunning = ref(false)

  const progressLabel = computed(() => formatProgressLabel(progress.value))

  let runId = 0
  const liveIntervals = new Set<ReturnType<typeof setInterval>>()

  const runWithProgress = async <T>(task: Promise<T>): Promise<T> => {
    const id = ++runId
    const startedAt = Date.now()
    progress.value = 0
    isRunning.value = true

    const interval = setInterval(() => {
      // A superseded run must not fight the newest one for the display.
      if (id !== runId) return
      const elapsed = Date.now() - startedAt
      // Clamp below 100 so the bar never claims completion before the request
      // has actually settled.
      progress.value = Math.min(99, Math.floor((elapsed / BRIDGE_WARMUP_MS) * 100))
    }, BRIDGE_PROGRESS_TICK_MS)
    liveIntervals.add(interval)

    const stopInterval = () => {
      clearInterval(interval)
      liveIntervals.delete(interval)
    }

    // Deliberately not captured for cancellation - see the note above.
    const warmup = new Promise<void>((resolve) => setTimeout(resolve, BRIDGE_WARMUP_MS))

    try {
      // Promise.all rejects as soon as the task does, without awaiting the
      // warm-up - that is what makes fast failures stay fast.
      const [result] = await Promise.all([task, warmup])
      stopInterval()
      if (id !== runId) return result

      progress.value = 100
      // Hold one tick so 100% is actually painted before the caller tears the
      // indicator down.
      await new Promise((resolve) => setTimeout(resolve, BRIDGE_PROGRESS_TICK_MS))
      if (id === runId) isRunning.value = false
      return result
    } catch (error) {
      stopInterval()
      if (id === runId) {
        progress.value = 0
        isRunning.value = false
      }
      throw error
    }
  }

  onBeforeUnmount(() => {
    for (const interval of liveIntervals) clearInterval(interval)
    liveIntervals.clear()
  })

  return { progress, progressLabel, isRunning, runWithProgress }
}
