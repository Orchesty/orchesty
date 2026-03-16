import { ref } from 'vue'

const THROTTLE_MS = 30_000

const lastActivityTime = ref(Date.now())
let initialized = false

function onUserActivity() {
  const now = Date.now()
  if (now - lastActivityTime.value > THROTTLE_MS) {
    lastActivityTime.value = now
  }
}

/**
 * Tracks user interaction events (click, keydown, mousemove, scroll, touch)
 * and exposes a throttled `lastActivityTime` timestamp.
 * Call `startTracking()` once from App.vue. Multiple calls are safe (idempotent).
 */
export function useActivityTracker() {
  function startTracking() {
    if (initialized) return
    initialized = true

    lastActivityTime.value = Date.now()

    const events: (keyof WindowEventMap)[] = ['click', 'keydown', 'mousemove', 'scroll', 'touchstart']
    events.forEach((event) => {
      window.addEventListener(event, onUserActivity, { passive: true, capture: true })
    })
  }

  function stopTracking() {
    if (!initialized) return
    initialized = false

    const events: (keyof WindowEventMap)[] = ['click', 'keydown', 'mousemove', 'scroll', 'touchstart']
    events.forEach((event) => {
      window.removeEventListener(event, onUserActivity, { capture: true })
    })
  }

  /** Mark the user as active right now (e.g. after login). */
  function touch() {
    lastActivityTime.value = Date.now()
  }

  return {
    lastActivityTime,
    startTracking,
    stopTracking,
    touch,
  }
}
