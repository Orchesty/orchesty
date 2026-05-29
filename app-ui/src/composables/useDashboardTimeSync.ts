import { ref, watch, onMounted, onActivated, onDeactivated } from 'vue'
import { convertTimeFilterToDateTimeRange } from '@/utils/timeRangeConverter'
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
   */
  const connectLoadData = (loadData: () => Promise<void> | void) => {
    watch(
      timeFilter,
      (newFilter) => {
        const range = convertTimeFilterToDateTimeRange(newFilter)
        dateTimeRange.value = { from: range.from, to: null }
        invalidate()
        if (isActive.value) loadData()
      },
    )

    if (refreshKey) {
      watch(refreshKey, () => {
        invalidate()
        loadData()
      })
    }

    onMounted(() => {
      loadData()
    })

    onActivated(() => {
      isActive.value = true
      if (isStale()) loadData()
    })

    onDeactivated(() => {
      isActive.value = false
    })
  }

  return { dateTimeRange, isActive, isStale, markFresh, invalidate, connectLoadData }
}
