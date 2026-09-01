import { ref } from 'vue'
import { DASHBOARD_DATA_MAX_AGE_MS } from '@/config/dashboard'

export function useTabDataFreshness() {
  const lastLoadedAt = ref<number | null>(null)
  const isActive = ref(true)

  const isStale = (): boolean => {
    if (lastLoadedAt.value === null) return true
    return Date.now() - lastLoadedAt.value > DASHBOARD_DATA_MAX_AGE_MS
  }

  const markFresh = () => {
    lastLoadedAt.value = Date.now()
  }

  const invalidate = () => {
    lastLoadedAt.value = null
  }

  return { lastLoadedAt, isActive, isStale, markFresh, invalidate }
}
