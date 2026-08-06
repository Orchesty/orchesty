/**
 * Process polling configuration for real-time canvas updates.
 *
 * After a process is triggered, the editor polls for metrics and process status
 * using a progressive interval strategy: fast at first, then slower.
 */

/** Interval between polls during the fast phase (ms) */
export const PROCESS_POLL_FAST_INTERVAL_MS = 2_000

/** Number of ticks in the fast phase before switching to slow */
export const PROCESS_POLL_FAST_COUNT = 5

/** Interval between polls during the slow phase (ms) */
export const PROCESS_POLL_SLOW_INTERVAL_MS = 2_000

/**
 * Optimistic warm-up window for publish/republish. The API returns as soon as k8s
 * accepts the Deployment, but the bridge still has to start, connect to
 * Mongo/Rabbit and declare its queues. There is no readiness endpoint to poll
 * (generator's Info() reports Spec.Replicas, not Status.ReadyReplicas), so we
 * hold for a fixed window. Replace with real polling if a probe is ever added.
 */
export const BRIDGE_WARMUP_MS = 15_000

/** Repaint interval for the warm-up progress bar (ms) */
export const BRIDGE_PROGRESS_TICK_MS = 100
