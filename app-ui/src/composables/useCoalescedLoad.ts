import { nextTick } from 'vue'

/**
 * Wraps a load function so that any number of trigger calls within the same
 * flush cycle collapse into a single invocation on the next microtask.
 *
 * Dashboard tabs have several independent reload triggers (grid filter watch,
 * refresh key watch, KeepAlive activation staleness check) that can all fire
 * in one flush — e.g. Vue 3.5 KeepAlive resumes paused watchers on activation
 * right before onActivated runs. Deferring by one tick also means the load
 * reads the final state of every reactive parameter set during the flush.
 */
export function createCoalescedLoader(load: () => Promise<void> | void): () => void {
  let scheduled = false

  return () => {
    if (scheduled) return
    scheduled = true
    nextTick(() => {
      scheduled = false
      load()
    })
  }
}
