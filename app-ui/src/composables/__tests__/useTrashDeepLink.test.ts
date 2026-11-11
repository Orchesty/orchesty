import { describe, it, expect, vi, beforeEach } from 'vitest'
import type { Mock } from 'vitest'
import { defineComponent, nextTick, reactive, ref } from 'vue'
import type { Ref } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import type { LocationQuery } from 'vue-router'
import type { TrashItem } from '@/types/trash'
import { fetchTrashItem } from '@/services/trashService'
import { useToast } from '@/composables/useToast'
import {
  isOnlyTrashIdChange,
  TRASH_ITEM_UNAVAILABLE_MESSAGE,
  useTrashDeepLink,
} from '../useTrashDeepLink'

const routerState = vi.hoisted(() => ({
  route: null as unknown as { query: LocationQuery; hash: string },
  replace: null as unknown as (to: { query: LocationQuery }) => Promise<void>,
}))

vi.mock('vue-router', () => ({
  useRoute: () => routerState.route,
  useRouter: () => ({ replace: routerState.replace }),
}))

vi.mock('@/services/trashService', () => ({
  fetchTrashItem: vi.fn(),
}))

const fetchMock = vi.mocked(fetchTrashItem)

const ITEM: TrashItem = {
  id: 'trash-1',
  topology: 'topo-1',
  topologyId: 'topo-1',
  node: 'node-1',
  nodeId: 'node-1',
  correlationId: 'corr-1',
  timestamp: '2026-10-21 08:14:03',
  headers: {},
  body: {},
}

let drawerOpen: Ref<boolean>
let selectedItem: Ref<TrashItem | null>
let openDrawer: Mock<(item: TrashItem) => void>
let replace: Mock<(to: { query: LocationQuery }) => Promise<void>>

function setup(query: LocationQuery) {
  routerState.route = reactive({ query, hash: '' })
  replace = vi.fn(async (to: { query: LocationQuery }) => {
    routerState.route.query = to.query
  })
  routerState.replace = replace

  drawerOpen = ref(false)
  selectedItem = ref<TrashItem | null>(null)
  openDrawer = vi.fn((item: TrashItem) => {
    selectedItem.value = item
    drawerOpen.value = true
  })

  let api!: ReturnType<typeof useTrashDeepLink>
  mount(defineComponent({
    setup() {
      api = useTrashDeepLink({ drawerOpen, selectedItem, openDrawer })
      return () => null
    },
  }))
  return api
}

describe('useTrashDeepLink', () => {
  beforeEach(() => {
    fetchMock.mockReset()
    useToast().toasts.value = []
  })

  it('opens the drawer with the fetched item when trashId is in the query', async () => {
    fetchMock.mockResolvedValue(ITEM)
    const { openFromRoute } = setup({ topologyId: 'topo-1', trashId: 'trash-1' })

    await openFromRoute()

    expect(fetchMock).toHaveBeenCalledWith('trash-1')
    expect(openDrawer).toHaveBeenCalledWith(ITEM)
    expect(drawerOpen.value).toBe(true)
    expect(replace).not.toHaveBeenCalled()
  })

  it('does nothing without trashId', async () => {
    const { openFromRoute } = setup({ topologyId: 'topo-1' })

    await openFromRoute()

    expect(fetchMock).not.toHaveBeenCalled()
    expect(openDrawer).not.toHaveBeenCalled()
  })

  it('shows a toast and removes trashId when the request fails', async () => {
    vi.spyOn(console, 'error').mockImplementation(() => {})
    fetchMock.mockRejectedValue(new Error('Request failed with status code 500'))
    const { openFromRoute } = setup({ topologyId: 'topo-1', trashId: 'missing' })

    await openFromRoute()

    expect(openDrawer).not.toHaveBeenCalled()
    expect(useToast().toasts.value.map((t) => t.message)).toEqual([TRASH_ITEM_UNAVAILABLE_MESSAGE])
    expect(replace).toHaveBeenCalledWith({ query: { topologyId: 'topo-1' }, hash: '' })
  })

  it('shows a toast and removes trashId when the id is not a trash item', async () => {
    fetchMock.mockResolvedValue(null)
    const { openFromRoute } = setup({ topologyId: 'topo-1', trashId: 'user-task-1' })

    await openFromRoute()

    expect(openDrawer).not.toHaveBeenCalled()
    expect(useToast().toasts.value.map((t) => t.message)).toEqual([TRASH_ITEM_UNAVAILABLE_MESSAGE])
    expect(replace).toHaveBeenCalledWith({ query: { topologyId: 'topo-1' }, hash: '' })
  })

  it('removes trashId when the drawer closes', async () => {
    fetchMock.mockResolvedValue(ITEM)
    const { openFromRoute } = setup({ topologyId: 'topo-1', trashId: 'trash-1' })
    await openFromRoute()

    drawerOpen.value = false
    await nextTick()

    expect(replace).toHaveBeenCalledTimes(1)
    expect(routerState.route.query).toEqual({ topologyId: 'topo-1' })
  })

  it('does not touch the query when the drawer closes without trashId', async () => {
    setup({ topologyId: 'topo-1' })
    drawerOpen.value = true
    await nextTick()

    drawerOpen.value = false
    await nextTick()

    expect(replace).not.toHaveBeenCalled()
  })

  it('does not refetch the item that is already open', async () => {
    fetchMock.mockResolvedValue(ITEM)
    const { openFromRoute } = setup({ trashId: 'trash-1' })

    await openFromRoute()
    await openFromRoute()

    expect(fetchMock).toHaveBeenCalledTimes(1)
  })

  it('ignores concurrent calls for the same id', async () => {
    fetchMock.mockResolvedValue(ITEM)
    const { openFromRoute } = setup({ trashId: 'trash-1' })

    await Promise.all([openFromRoute(), openFromRoute()])

    expect(fetchMock).toHaveBeenCalledTimes(1)
    expect(openDrawer).toHaveBeenCalledTimes(1)
  })

  it('ignores a response for a trashId that is no longer in the query', async () => {
    let resolve!: (item: TrashItem) => void
    fetchMock.mockReturnValue(new Promise((r) => { resolve = r }))
    const { openFromRoute } = setup({ trashId: 'trash-1' })

    const pending = openFromRoute()
    routerState.route.query = { topologyId: 'topo-1' }
    resolve(ITEM)
    await pending
    await flushPromises()

    expect(openDrawer).not.toHaveBeenCalled()
  })
})

describe('isOnlyTrashIdChange', () => {
  it.each([
    [{ topologyId: 't', trashId: 'a' }, { topologyId: 't' }, true],
    [{ topologyId: 't' }, { topologyId: 't', trashId: 'a' }, true],
    [{ topologyId: 't', trashId: 'b' }, { topologyId: 't', trashId: 'a' }, true],
    [{}, {}, true],
    [{ topologyId: 't2', trashId: 'a' }, { topologyId: 't', trashId: 'a' }, false],
    [{ topologyId: 't', search: 'x' }, { topologyId: 't' }, false],
    [{ node: ['n1', 'n2'] }, { node: ['n1', 'n2'], trashId: 'a' }, true],
    [{ node: ['n1', 'n2'] }, { node: ['n1'] }, false],
  ] as [LocationQuery, LocationQuery, boolean][])('%j vs %j -> %s', (next, prev, expected) => {
    expect(isOnlyTrashIdChange(next, prev)).toBe(expected)
  })
})
