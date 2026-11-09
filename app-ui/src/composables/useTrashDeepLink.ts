import { watch } from 'vue'
import type { Ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { LocationQuery } from 'vue-router'
import type { TrashItem } from '@/types/trash'
import { fetchTrashItem } from '@/services/trashService'
import { useToast } from '@/composables/useToast'

export const TRASH_ID_QUERY_KEY = 'trashId'

export const TRASH_ITEM_UNAVAILABLE_MESSAGE =
  'This failed message is no longer available (it may have been approved or rejected).'

interface UseTrashDeepLinkOptions {
  drawerOpen: Ref<boolean>
  selectedItem: Ref<TrashItem | null>
  openDrawer: (item: TrashItem) => void
}

function queryValueToString(value: LocationQuery[string] | undefined): string {
  if (Array.isArray(value)) return value.filter((v) => v !== null).join(',')
  return value ?? ''
}

function omitTrashId(query: LocationQuery): LocationQuery {
  return Object.fromEntries(Object.entries(query).filter(([key]) => key !== TRASH_ID_QUERY_KEY))
}

/**
 * True when the two queries differ at most in `trashId`, i.e. the list filters
 * are unchanged and only the drawer deep link was added, changed or removed.
 */
export function isOnlyTrashIdChange(next: LocationQuery, prev: LocationQuery): boolean {
  const a = omitTrashId(next)
  const b = omitTrashId(prev)
  const keys = new Set([...Object.keys(a), ...Object.keys(b)])
  for (const key of keys) {
    if (queryValueToString(a[key]) !== queryValueToString(b[key])) return false
  }
  return true
}

/**
 * Opens the trash detail drawer for `?trashId=<id>` (e.g. links from notification
 * emails) and keeps the query in sync: closing the drawer removes `trashId`, and
 * a missing/non-trash item shows a toast and removes it as well.
 */
export function useTrashDeepLink({ drawerOpen, selectedItem, openDrawer }: UseTrashDeepLinkOptions) {
  const route = useRoute()
  const router = useRouter()
  const { showToast } = useToast()

  let inFlightId: string | null = null

  function readTrashId(): string {
    const value = route.query[TRASH_ID_QUERY_KEY]
    const first = Array.isArray(value) ? value[0] : value
    return typeof first === 'string' ? first.trim() : ''
  }

  function removeTrashId() {
    if (!(TRASH_ID_QUERY_KEY in route.query)) return
    void router.replace({ query: omitTrashId(route.query), hash: route.hash })
  }

  async function openFromRoute() {
    const id = readTrashId()
    if (!id || id === inFlightId) return
    if (drawerOpen.value && selectedItem.value?.id === id) return

    inFlightId = id
    try {
      const item = await fetchTrashItem(id)
      // The user may have navigated elsewhere while the request was pending
      if (readTrashId() !== id) return
      if (item) {
        openDrawer(item)
        return
      }
      showToast(TRASH_ITEM_UNAVAILABLE_MESSAGE, 'warning')
      removeTrashId()
    } catch (error) {
      console.error('Failed to load failed message:', error)
      if (readTrashId() !== id) return
      showToast(TRASH_ITEM_UNAVAILABLE_MESSAGE, 'warning')
      removeTrashId()
    } finally {
      inFlightId = null
    }
  }

  watch(drawerOpen, (open) => {
    if (!open) removeTrashId()
  })

  return {
    openFromRoute,
    isOnlyTrashIdChange,
  }
}
