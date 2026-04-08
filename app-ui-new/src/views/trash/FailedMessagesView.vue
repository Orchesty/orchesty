<script setup lang="ts">
import { ref, onMounted, onActivated, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import DateTimeRangeFilter from '@/components/ui/datagrid/DateTimeRangeFilter.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import SearchableDropdownFilter from '@/components/ui/datagrid/SearchableDropdownFilter.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Confirm from '@/components/ui/Confirm.vue'
import MoreActions from '@/components/ui/MoreActions.vue'
import type { MoreActionsSection } from '@/components/ui/MoreActions.vue'
import TrashDetailDrawer from '@/components/trash/TrashDetailDrawer.vue'
import type { TrashItem, TrashQueryParams } from '@/types/trash'
import type { BulkAction } from '@/types/datagrid'
import type { TableColumn } from '@/types/dashboard'
import {
  fetchTrashItems,
  bulkApprove,
  bulkReject,
  approveTrashItem,
  rejectTrashItem,
  updateTrashItem,
  approveByFilter,
  rejectByFilter,
} from '@/services/trashService'
import type { TrashFilterParams } from '@/services/trashService'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApiFilter } from '@/utils/timeRangeConverter'
import { useDataGrid } from '@/composables/useDataGrid'
import { useTopologyNodeFilter } from '@/composables/useTopologyNodeFilter'
import { useToast } from '@/composables/useToast'
import { useDateFormat } from '@/composables/useDateFormat'

const route = useRoute()
const { formatDateTime } = useDateFormat()

const {
  topologyFilter,
  nodeFilter,
  topologyOptions,
  nodeOptions,
  getTopologyName,
  getNodeName,
  getNodeIdsByName,
  mappings,
} = useTopologyNodeFilter()

/** Deep-link from connector audit: raw Mongo node id(s); wins over dropdown until user edits node filter */
const prefilledNodeIdsFromQuery = ref<string[] | null>(null)
/** Deep-link time preset (e.g. 24h, 7d); used when custom date range is not set */
const timeRangeFromQuery = ref<string | null>(null)
const syncingNodeFilterFromQuery = ref(false)
const skipFilterAutoLoad = ref(false)

// Toast notifications
const { showToast } = useToast()

// State
const trashItems = ref<TrashItem[]>([])
const selectedRows = ref<Set<string>>(new Set())

// Filters
const searchFilter = ref('')
const correlationIdFilter = ref('')
const dateTimeRange = ref<{ from: string | null; to: string | null }>({
  from: null,
  to: null,
})

// Drawer state
const drawerOpen = ref(false)
const selectedItem = ref<TrashItem | null>(null)

// Confirm modal states
const bulkApproveConfirmOpen = ref(false)
const bulkRejectConfirmOpen = ref(false)
const approveAllConfirmOpen = ref(false)
const rejectAllConfirmOpen = ref(false)
const approveAllProcessing = ref(false)
const rejectAllProcessing = ref(false)

// Count computed properties
const selectedCount = computed(() => selectedRows.value.size)

// Action handlers (defined before menus that reference them)
function handleBulkApprove(selectedIds: Set<string>) {
  if (selectedIds.size === 0) return
  bulkApproveConfirmOpen.value = true
}

function handleBulkReject(selectedIds: Set<string>) {
  if (selectedIds.size === 0) return
  bulkRejectConfirmOpen.value = true
}

async function confirmBulkApprove() {
  try {
    const count = selectedRows.value.size
    await bulkApprove(Array.from(selectedRows.value))
    showToast(`${count} message(s) approved successfully`, 'success')
    selectedRows.value = new Set()
    loadData()
  } catch (error) {
    console.error('Bulk approve failed:', error)
    showToast('Failed to approve messages', 'error')
  }
}

async function confirmBulkReject() {
  try {
    const count = selectedRows.value.size
    await bulkReject(Array.from(selectedRows.value))
    showToast(`${count} message(s) rejected successfully`, 'success')
    selectedRows.value = new Set()
    loadData()
  } catch (error) {
    console.error('Bulk reject failed:', error)
    showToast('Failed to reject messages', 'error')
  }
}

const handleApproveAll = () => {
  approveAllConfirmOpen.value = true
}

const handleRejectAll = () => {
  rejectAllConfirmOpen.value = true
}

const moreActionsSections: MoreActionsSection[] = [
  {
    items: [
      { type: 'button', label: 'Approve All Filtered', onClick: handleApproveAll },
      { type: 'button', label: 'Reject All Filtered', onClick: handleRejectAll },
    ],
  },
]

function buildBatchFilterParams(): TrashFilterParams {
  const filter: TrashFilterParams = {}

  if (topologyFilter.value) filter.topologyId = topologyFilter.value
  if (correlationIdFilter.value) filter.correlationId = correlationIdFilter.value
  if (searchFilter.value) filter.search = searchFilter.value

  if (prefilledNodeIdsFromQuery.value?.length) {
    filter.nodeId = prefilledNodeIdsFromQuery.value
  } else if (nodeFilter.value) {
    const nodeIds = getNodeIdsByName(nodeFilter.value)
    filter.nodeId = nodeIds.length > 0 ? nodeIds : [nodeFilter.value]
  }

  if (dateTimeRange.value.from && dateTimeRange.value.to) {
    filter.dateFrom = dateTimeRange.value.from
    filter.dateTo = dateTimeRange.value.to
  } else if (timeRangeFromQuery.value) {
    const dateRange = convertTimeFilterToDateTimeRange(timeRangeFromQuery.value)
    filter.dateFrom = formatDateTimeForApiFilter(dateRange.from)
  }

  return filter
}

const confirmApproveAll = async () => {
  approveAllProcessing.value = true
  try {
    await approveByFilter(buildBatchFilterParams())
    showToast('Messages approved successfully', 'success')
    selectedRows.value = new Set()
    loadData()
  } catch (error) {
    console.error('Approve all failed:', error)
    showToast('Failed to approve all messages', 'error')
  } finally {
    approveAllProcessing.value = false
    approveAllConfirmOpen.value = false
  }
}

const confirmRejectAll = async () => {
  rejectAllProcessing.value = true
  try {
    await rejectByFilter(buildBatchFilterParams())
    showToast('Messages rejected successfully', 'success')
    selectedRows.value = new Set()
    loadData()
  } catch (error) {
    console.error('Reject all failed:', error)
    showToast('Failed to reject all messages', 'error')
  } finally {
    rejectAllProcessing.value = false
    rejectAllConfirmOpen.value = false
  }
}

// Table columns
const columns: TableColumn[] = [
  { key: 'topology', label: 'Topology', sortable: false },
  { key: 'node', label: 'Node', sortable: false },
  { key: 'timestamp', label: 'Timestamp', sortable: true },
  { key: 'resultMessage', label: 'Result Message', sortable: false },
  { key: 'actions', label: '', className: 'text-right w-16' },
]

// Bulk actions
const bulkActions: BulkAction[] = [
  {
    label: 'Approve',
    variant: 'primary',
    onClick: handleBulkApprove,
  },
  {
    label: 'Reject',
    variant: 'danger',
    onClick: handleBulkReject,
  },
]

// Build query params from current filter state
const buildCurrentFilterParams = (): TrashQueryParams => {
  const params: TrashQueryParams = {
    page: currentPage.value,
    perPage: itemsPerPage.value,
    sortBy: sortField.value,
    sortOrder: sortDirection.value,
  }

  if (searchFilter.value) params.search = searchFilter.value
  if (correlationIdFilter.value) params.correlationId = correlationIdFilter.value
  if (prefilledNodeIdsFromQuery.value?.length) {
    params.node = prefilledNodeIdsFromQuery.value
  } else if (nodeFilter.value) {
    const nodeIds = getNodeIdsByName(nodeFilter.value)
    params.node = nodeIds.length > 0 ? nodeIds : [nodeFilter.value]
  }
  if (topologyFilter.value) params.topology = topologyFilter.value
  if (dateTimeRange.value.from && dateTimeRange.value.to) {
    params.dateFrom = dateTimeRange.value.from
    params.dateTo = dateTimeRange.value.to
  } else if (timeRangeFromQuery.value) {
    params.timeRange = timeRangeFromQuery.value
  }

  return params
}

function applyTrashQueryFromRoute() {
  const q = route.query

  const corrRaw = q.correlationId
  const corr =
    typeof corrRaw === 'string' ? corrRaw : Array.isArray(corrRaw) ? corrRaw[0] : ''
  if (typeof corr === 'string' && corr.trim()) {
    correlationIdFilter.value = corr.trim()
  } else {
    correlationIdFilter.value = ''
  }

  const nodeRaw = q.node
  const nodeStr =
    typeof nodeRaw === 'string'
      ? nodeRaw
      : Array.isArray(nodeRaw)
        ? nodeRaw.join(',')
        : ''
  if (nodeStr.trim()) {
    prefilledNodeIdsFromQuery.value = nodeStr.split(',').map((s) => s.trim()).filter(Boolean)
  } else {
    prefilledNodeIdsFromQuery.value = null
  }

  const topoRaw = q.topologyId
  const topoId = typeof topoRaw === 'string' ? topoRaw : Array.isArray(topoRaw) ? topoRaw[0] : ''
  if (typeof topoId === 'string' && topoId.trim()) {
    topologyFilter.value = topoId.trim()
  }

  const searchRaw = q.search
  const searchStr = typeof searchRaw === 'string' ? searchRaw : Array.isArray(searchRaw) ? searchRaw[0] : ''
  if (typeof searchStr === 'string' && searchStr.trim()) {
    searchFilter.value = searchStr.trim()
  } else {
    searchFilter.value = ''
  }

  const trRaw = q.timeRange
  const tr =
    typeof trRaw === 'string' ? trRaw : Array.isArray(trRaw) ? trRaw[0] : ''
  if (typeof tr === 'string' && tr.trim()) {
    timeRangeFromQuery.value = tr.trim()
  } else {
    timeRangeFromQuery.value = null
  }

  syncNodeFilterLabelFromPrefilledIds()
}

function syncNodeFilterLabelFromPrefilledIds() {
  const ids = prefilledNodeIdsFromQuery.value
  if (!ids?.length) return
  const id = ids[0]
  if (id === undefined) return
  const name = getNodeName(id)
  syncingNodeFilterFromQuery.value = true
  nodeFilter.value = name
  syncingNodeFilterFromQuery.value = false
}

watch(
  () => mappings.value,
  () => {
    if (prefilledNodeIdsFromQuery.value?.length) {
      syncNodeFilterLabelFromPrefilledIds()
    }
  },
  { immediate: true },
)

watch(nodeFilter, () => {
  if (syncingNodeFilterFromQuery.value) return
  prefilledNodeIdsFromQuery.value = null
})

watch(
  dateTimeRange,
  () => {
    const dr = dateTimeRange.value
    if (dr.from && dr.to && timeRangeFromQuery.value) {
      timeRangeFromQuery.value = null
    }
  },
  { deep: true },
)

// Load data
const loadData = async () => {
  loading.value = true

  try {
    const response = await fetchTrashItems(buildCurrentFilterParams())
    trashItems.value = response.data
    totalItems.value = response.pagination.total
    totalPages.value = response.pagination.totalPages
  } catch (error) {
    console.error('Failed to load trash items:', error)
  } finally {
    loading.value = false
  }
}

// Use DataGrid composable
const {
  currentPage,
  itemsPerPage,
  totalPages,
  totalItems,
  sortField,
  sortDirection,
  loading,
  handlePageChange,
  handlePerPageChange,
  handleSort,
} = useDataGrid({
  defaultSort: { field: 'timestamp', direction: 'desc' },
  onDataLoad: loadData,
  filters: [searchFilter, correlationIdFilter, nodeFilter, topologyFilter, dateTimeRange],
  skipAutoLoad: skipFilterAutoLoad,
})

function hydrateFromRouteAndLoad() {
  skipFilterAutoLoad.value = true
  applyTrashQueryFromRoute()
  skipFilterAutoLoad.value = false
  void loadData()
}

const mounted = ref(false)

onMounted(() => {
  hydrateFromRouteAndLoad()
  mounted.value = true
})

onActivated(() => {
  if (mounted.value) loadData()
})

watch(
  () => route.query,
  () => {
    hydrateFromRouteAndLoad()
  },
  { deep: true },
)

// Drawer handlers
const openDrawer = (item: TrashItem) => {
  selectedItem.value = item
  drawerOpen.value = true
}

const handleApprove = async () => {
  if (!selectedItem.value) return

  try {
    await approveTrashItem(selectedItem.value.id)
    showToast('Message approved successfully', 'success')
    drawerOpen.value = false
    loadData()
  } catch (error) {
    console.error('Approve failed:', error)
    showToast('Failed to approve message', 'error')
  }
}

const handleUpdate = async (data: { headers: Record<string, unknown>; body: Record<string, unknown> }) => {
  if (!selectedItem.value) return

  try {
    const updatedData = await updateTrashItem(selectedItem.value.id, data)
    // Update the selectedItem with the data returned from API
    selectedItem.value.headers = updatedData.headers
    selectedItem.value.body = updatedData.body
    showToast('Message updated successfully', 'success')
    // Keep drawer open so user can approve after editing
    loadData()
  } catch (error) {
    console.error('Update failed:', error)
    showToast('Failed to update message', 'error')
  }
}

const handleReject = async () => {
  if (!selectedItem.value) return

  try {
    await rejectTrashItem(selectedItem.value.id)
    showToast('Message rejected successfully', 'success')
    drawerOpen.value = false
    loadData()
  } catch (error) {
    console.error('Reject failed:', error)
    showToast('Failed to reject message', 'error')
  }
}

</script>

<template>
  <main class="h-full overflow-y-auto">
    <div class="px-4 pb-4 pt-6">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Failed Messages</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        View failed messages from all topologies
      </p>
    </div>

    <!-- Card -->
    <Card>
      <DataGrid
        :columns="columns"
        :data="trashItems"
        :loading="loading"
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalItems"
        :items-per-page="itemsPerPage"
        :sort-field="sortField"
        :sort-direction="sortDirection"
        :bulk-actions="bulkActions"
        :selected-rows="selectedRows"
        row-id-key="id"
        show-refresh
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @sort="handleSort"
        @refresh="loadData"
        @update:selected-rows="selectedRows = $event"
      >
        <template #search>
          <div class="flex flex-col items-end gap-2">
            <MoreActions
              id="trash-grid-actions"
              :sections="moreActionsSections"
            />
            <SearchInput
              v-model="searchFilter"
              placeholder="Search"
              mode="server"
              width="w-72"
            />
          </div>
        </template>

        <template #filters>
          <TextInput
            v-model="correlationIdFilter"
            placeholder="Correlation ID"
          />
          <SearchableDropdownFilter
            v-model="nodeFilter"
            :options="nodeOptions"
            placeholder="All Nodes"
            search-placeholder="Search nodes..."
          />
          <SearchableDropdownFilter
            v-model="topologyFilter"
            :options="topologyOptions"
            placeholder="All Topologies"
            search-placeholder="Search topologies..."
          />
          <DateTimeRangeFilter v-model="dateTimeRange" />
        </template>

        <!-- Custom cell templates -->
        <template #cell-node="{ row }">
          <span class="text-sm text-gray-900 dark:text-white">
            {{ getNodeName(row.nodeId) }}
          </span>
        </template>

        <template #cell-topology="{ row }">
          <RouterLink
            :to="`/topologies/${row.topologyId}`"
            class="font-medium text-gray-900 hover:underline dark:text-white"
          >
            {{ getTopologyName(row.topologyId) }}
          </RouterLink>
        </template>

        <template #cell-timestamp="{ value }">
          {{ formatDateTime(value) }}
        </template>

        <template #cell-resultMessage="{ row }">
          {{ row.headers['result-message'] || '' }}
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end">
            <button
              type="button"
              title="View details"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              @click="openDrawer(row as TrashItem)"
            >
              <svg
                class="h-5 w-5"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                />
              </svg>
              <span class="sr-only">View details</span>
            </button>
          </div>
        </template>
      </DataGrid>
    </Card>

    <!-- Drawer -->
    <TrashDetailDrawer
      v-model="drawerOpen"
      :item="selectedItem"
      @approve="handleApprove"
      @update="handleUpdate"
      @reject="handleReject"
    />

    <!-- Confirm Modals for Bulk Actions -->
    <Confirm
      v-model="bulkApproveConfirmOpen"
      id="bulk-approve-confirm"
      confirm-text="Yes, approve"
      cancel-text="Cancel"
      confirm-variant="primary"
      @confirm="confirmBulkApprove"
    >
      <svg
        class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 20 20"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        />
      </svg>
      <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
        Are you sure you want to approve {{ selectedCount }} selected
        {{ selectedCount === 1 ? 'message' : 'messages' }}?
      </h3>
    </Confirm>

    <Confirm
      v-model="bulkRejectConfirmOpen"
      id="bulk-reject-confirm"
      confirm-text="Yes, reject"
      cancel-text="Cancel"
      @confirm="confirmBulkReject"
    >
      <svg
        class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 20 20"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        />
      </svg>
      <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
        Are you sure you want to reject {{ selectedCount }} selected
        {{ selectedCount === 1 ? 'message' : 'messages' }}?
      </h3>
    </Confirm>

    <!-- Confirm Modals for More Actions -->
    <Confirm
      v-model="approveAllConfirmOpen"
      id="approve-all-confirm"
      confirm-text="Yes, approve all"
      cancel-text="Cancel"
      confirm-variant="primary"
      :loading="approveAllProcessing"
      @confirm="confirmApproveAll"
    >
      <svg
        class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 20 20"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        />
      </svg>
      <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
        Are you sure you want to approve all {{ totalItems }}
        {{ totalItems === 1 ? 'message' : 'messages' }}?
      </h3>
    </Confirm>

    <Confirm
      v-model="rejectAllConfirmOpen"
      id="reject-all-confirm"
      confirm-text="Yes, reject all"
      cancel-text="Cancel"
      :loading="rejectAllProcessing"
      @confirm="confirmRejectAll"
    >
      <svg
        class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 20 20"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        />
      </svg>
      <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
        Are you sure you want to reject all {{ totalItems }}
        {{ totalItems === 1 ? 'message' : 'messages' }}?
      </h3>
    </Confirm>
    </div>
  </main>
</template>

