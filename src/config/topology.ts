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
