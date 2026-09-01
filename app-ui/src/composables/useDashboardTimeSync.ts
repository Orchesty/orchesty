import { ref, watch, onActivated, onDeactivated } from 'vue'
import { convertTimeFilterToDateTimeRange } from '@/utils/timeRangeConverter'
import { createCoalescedLoader } from '@/composables/useCoalescedLoad'
import { useTabDataFreshness } from '@/composables/useTabDataFreshness'
import type { TimeFilter } from '@/types/dashboard'

interface UseDashboardTimeSyncOptions {
  timeFilter: () => TimeFilter
  refreshKey?: () => number | undefined
}

/**
 * Encapsulates common dashboard tab state:
 * - dateTimeRange synced from timeFilter
 * - Tab freshness management (isActive, isStale, markFresh, invalidate)
 * - onActivated / onDeactivated lifecycle
 *
 * Returns a `connectLoadData` function that the component calls
 * after defining its `loadData` to wire up the watches.
 */
export function useDashboardTimeSync(options: UseDashboardTimeSyncOptions) {
  const { timeFilter, refreshKey } = options
  const { isActive, isStale, markFresh, invalidate } = useTabDataFreshness()

  const dateTimeRange = ref<{ from: string | null; to: string | null }>({
    from: null,
    to: null,
  })

  // Set initial dateTimeRange synchronously
  const initialRange = convertTimeFilterToDateTimeRange(timeFilter())
  dateTimeRange.value = { from: initialRange.from, to: null }

  /**
   * Call after defining loadData to register all watches and lifecycle hooks.
   *
   * Returns a coalesced trigger that collapses same-flush calls into one load —
   * pass it as `onDataLoad` to useDataGrid so the grid's filter watch (the sole
   * immediate trigger for time changes, via the shared dateTimeRange ref) and
   * the activation staleness check cannot double-fire the same request.
   */
  const connectLoadData = (loadData: () => Promise<void> | void): (() => void) => {
    const requestLoad = createCoalescedLoader(loadData)

    watch(
      timeFilter,
      (newFilter) => {
        const range = convertTimeFilterToDateTimeRange(newFilter)
        dateTimeRange.value = { from: range.from, to: null }
        invalidate()
      },
    )

    if (refreshKey) {
      watch(refreshKey, () => {
        invalidate()
        requestLoad()
      })
    }

    onActivated(() => {
      isActive.value = true
      if (isStale()) requestLoad()
    })

    onDeactivated(() => {
      isActive.value = false
    })

    return requestLoad
  }

  return { dateTimeRange, isActive, isStale, markFresh, invalidate, connectLoadData }
}
