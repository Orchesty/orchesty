<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import TimeRangeFilterWithCustomRange from '@/components/ui/TimeRangeFilterWithCustomRange.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import SearchableDropdownFilter from '@/components/ui/datagrid/SearchableDropdownFilter.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import DropdownMenu, { type DropdownMenuSection } from '@/components/ui/DropdownMenu.vue'
import Confirm from '@/components/ui/Confirm.vue'
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
} from '@/services/trashService'
import { useDataGrid } from '@/composables/useDataGrid'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useToast } from '@/composables/useToast'
import { useDateFormat } from '@/composables/useDateFormat'

const route = useRoute()
const { formatDateTime } = useDateFormat()

// Topology/Node mappings composable
const {
  loadMappings,
  getTopologyName,
  getNodeName,
  getNodeIdsByName,
  topologyOptions: topologyOptionsFromMappings,
  deduplicatedNodeOptions: deduplicatedNodeOptionsFromMappings,
  nodeOptions: nodeOptionsFromMappings,
  mappings
} = useTopologyNodeMappings()

// Toast notifications
const { showToast } = useToast()

// State
const trashItems = ref<TrashItem[]>([])
const selectedRows = ref<Set<string>>(new Set())

// Filters
const searchFilter = ref('')
const correlationIdFilter = ref('')
const nodeFilter = ref<string | null>(null)
const topologyFilter = ref<string | null>(null)
const timeRangeFilter = ref('this-month')

// Topology options for dropdown with "All" option
const topologyOptions = computed(() => [
  { value: null, label: 'All Topologies' },
  ...topologyOptionsFromMappings.value
])

// Node options for dropdown - deduplicated by name, filtered by selected topology
const nodeOptions = computed(() => {
  const baseOptions = [{ value: null, label: 'All Nodes' }]

  if (!topologyFilter.value || !mappings.value) {
    return [...baseOptions, ...deduplicatedNodeOptionsFromMappings.value]
  }

  // Get node IDs for the selected topology from the tree
  const nodeIdsInTopology = mappings.value.tree[topologyFilter.value] || []

  // Get unique names of nodes in this topology
  const namesInTopology = new Set(
    nodeIdsInTopology
      .map(id => mappings.value?.nodes[id])
      .filter((name): name is string => !!name)
  )

  const filteredNodes = Array.from(namesInTopology)
    .map(name => ({ value: name, label: name }))
    .sort((a, b) => a.label.localeCompare(b.label))

  return [...baseOptions, ...filteredNodes]
})

// Clear node filter when topology changes if selected node name is not in new topology
watch(topologyFilter, () => {
  if (nodeFilter.value && topologyFilter.value && mappings.value) {
    const nodeIdsInTopology = mappings.value.tree[topologyFilter.value] || []
    const namesInTopology = new Set(
      nodeIdsInTopology
        .map(id => mappings.value?.nodes[id])
        .filter(Boolean)
    )

    if (!namesInTopology.has(nodeFilter.value)) {
      nodeFilter.value = null
    }
  }
})

// Drawer state
const drawerOpen = ref(false)
const selectedItem = ref<TrashItem | null>(null)

// Confirm modal states
const bulkApproveConfirmOpen = ref(false)
const bulkRejectConfirmOpen = ref(false)
const approveAllConfirmOpen = ref(false)
const rejectAllConfirmOpen = ref(false)

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

const confirmApproveAll = async () => {
  try {
    // Fetch all items matching current filters to collect IDs
    const params = buildCurrentFilterParams()
    params.page = 1
    params.perPage = totalItems.value || 9999
    const response = await fetchTrashItems(params)
    const ids = response.data.map((item) => item.id)

    if (ids.length > 0) {
      await bulkApprove(ids)
    }

    showToast(`${ids.length} message(s) approved successfully`, 'success')
    selectedRows.value = new Set()
    loadData()
  } catch (error) {
    console.error('Approve all failed:', error)
    showToast('Failed to approve all messages', 'error')
  }
}

const confirmRejectAll = async () => {
  try {
    // Fetch all items matching current filters to collect IDs
    const params = buildCurrentFilterParams()
    params.page = 1
    params.perPage = totalItems.value || 9999
    const response = await fetchTrashItems(params)
    const ids = response.data.map((item) => item.id)

    if (ids.length > 0) {
      await bulkReject(ids)
    }

    showToast(`${ids.length} message(s) rejected successfully`, 'success')
    selectedRows.value = new Set()
    loadData()
  } catch (error) {
    console.error('Reject all failed:', error)
    showToast('Failed to reject all messages', 'error')
  }
}

// More actions dropdown menu
const moreActionsMenuSections: DropdownMenuSection[] = [
  {
    items: [
      { type: 'button', label: 'Approve All Filtered', onClick: handleApproveAll },
      { type: 'button', label: 'Reject All Filtered', onClick: handleRejectAll },
    ],
  },
]

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
  if (nodeFilter.value) {
    const nodeIds = getNodeIdsByName(nodeFilter.value)
    params.node = nodeIds.length > 0 ? nodeIds : [nodeFilter.value]
  }
  if (topologyFilter.value) params.topology = topologyFilter.value
  if (timeRangeFilter.value) params.timeRange = timeRangeFilter.value

  return params
}

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
  filters: [searchFilter, correlationIdFilter, nodeFilter, topologyFilter, timeRangeFilter],
})

// Load mappings and data
onMounted(async () => {
  await loadMappings()

  // Check for correlationId in query params
  if (route.query.correlationId) {
    correlationIdFilter.value = route.query.correlationId as string
  }

  loadData()
})

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
  <DashboardLayout>
    <!-- Page Header with Actions Button -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Failed Messages</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          View failed messages from all topologies
        </p>
      </div>

      <!-- More actions dropdown -->
      <DropdownMenu
        id="trash-more-dropdown"
        width="w-44"
        :sections="moreActionsMenuSections"
      >
        <template #trigger>
          <span class="inline-flex items-center justify-center h-9 w-9 rounded-full border border-gray-200 bg-white text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700">
            <svg
              class="h-5 w-5"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 16 3"
            >
              <path
                d="M2 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm6.041 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM14 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z"
              />
            </svg>
            <span class="sr-only">More actions</span>
          </span>
        </template>
      </DropdownMenu>
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
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @sort="handleSort"
        @update:selected-rows="selectedRows = $event"
      >
        <template #filters>
          <SearchInput
            v-model="searchFilter"
            placeholder="Search"
            mode="server"
            width="w-72"
          />
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
          <TimeRangeFilterWithCustomRange v-model="timeRangeFilter" />
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
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
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
      :get-topology-name="getTopologyName"
      :get-node-name="getNodeName"
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
  </DashboardLayout>
</template>

