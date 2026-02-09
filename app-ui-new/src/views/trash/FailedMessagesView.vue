<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import TimeRangeFilterWithCustomRange from '@/components/ui/TimeRangeFilterWithCustomRange.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import DropdownMenu, { type DropdownMenuSection } from '@/components/ui/DropdownMenu.vue'
import Confirm from '@/components/ui/Confirm.vue'
import TrashDetailDrawer from '@/components/trash/TrashDetailDrawer.vue'
import type { TrashItem, TrashQueryParams } from '@/types/trash'
import type { BulkAction } from '@/types/datagrid'
import type { TableColumn } from '@/types/dashboard'
import {
  fetchTrashItems,
  fetchTopologyNames,
  bulkApprove,
  bulkReject,
  approveTrashItem,
  rejectTrashItem,
  updateTrashItem,
} from '@/services/trashService'
import { useDataGrid } from '@/composables/useDataGrid'

// State
const trashItems = ref<TrashItem[]>([])
const selectedRows = ref<Set<string>>(new Set())

// Filters
const correlationIdFilter = ref('')
const nodeFilter = ref('')
const topologyFilter = ref<string | null>(null)
const timeRangeFilter = ref('this-month')

// Topology options for dropdown
const topologyOptions = ref<{ value: string | null; label: string }[]>([
  { value: null, label: 'All Topologies' },
])

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
    await bulkApprove(Array.from(selectedRows.value))
    selectedRows.value.clear()
    loadData()
  } catch (error) {
    console.error('Bulk approve failed:', error)
  }
}

async function confirmBulkReject() {
  try {
    await bulkReject(Array.from(selectedRows.value))
    selectedRows.value.clear()
    loadData()
  } catch (error) {
    console.error('Bulk reject failed:', error)
  }
}

const handleApproveAll = () => {
  approveAllConfirmOpen.value = true
}

const handleRejectAll = () => {
  rejectAllConfirmOpen.value = true
}

const confirmApproveAll = async () => {
  // TODO: Implement approve all
  console.log('Approve all confirmed')
  loadData()
}

const confirmRejectAll = async () => {
  // TODO: Implement reject all
  console.log('Reject all confirmed')
  loadData()
}

// More actions dropdown menu
const moreActionsMenuSections: DropdownMenuSection[] = [
  {
    items: [
      { type: 'button', label: 'Approve All', onClick: handleApproveAll },
      { type: 'button', label: 'Reject All', onClick: handleRejectAll },
    ],
  },
]

// Table columns
const columns: TableColumn[] = [
  { key: 'topology', label: 'Topology', sortable: true },
  { key: 'node', label: 'Node', sortable: true },
  { key: 'timestamp', label: 'Timestamp', sortable: true },
  { key: 'resultMessage', label: 'Result Message', sortable: false },
  { key: 'actions', label: '', className: 'text-right' },
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

// Load data
const loadData = async () => {
  loading.value = true
  
  const params: TrashQueryParams = {
    page: currentPage.value,
    perPage: itemsPerPage.value,
    sortBy: sortField.value,
    sortOrder: sortDirection.value,
  }
  
  if (correlationIdFilter.value) {
    params.correlationId = correlationIdFilter.value
  }
  
  if (nodeFilter.value) {
    params.node = nodeFilter.value
  }
  
  if (topologyFilter.value) {
    params.topology = topologyFilter.value
  }
  
  // TODO: Convert timeRangeFilter to dateFrom/dateTo when needed
  if (timeRangeFilter.value) {
    params.timeRange = timeRangeFilter.value
  }
  
  try {
    const response = await fetchTrashItems(params)
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
  filters: [correlationIdFilter, nodeFilter, topologyFilter, timeRangeFilter],
})

// Load topology names
onMounted(async () => {
  const topologies = await fetchTopologyNames()
  topologyOptions.value = [
    { value: null, label: 'All Topologies' },
    ...topologies.map((t) => ({ value: t, label: t })),
  ]
  
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
    drawerOpen.value = false
    loadData()
  } catch (error) {
    console.error('Approve failed:', error)
  }
}

const handleUpdate = async (data: { headers: Record<string, unknown>; body: Record<string, unknown> }) => {
  if (!selectedItem.value) return
  
  try {
    await updateTrashItem(selectedItem.value.id, data)
    drawerOpen.value = false
    loadData()
  } catch (error) {
    console.error('Update failed:', error)
  }
}

const handleReject = async () => {
  if (!selectedItem.value) return
  
  try {
    await rejectTrashItem(selectedItem.value.id)
    drawerOpen.value = false
    loadData()
  } catch (error) {
    console.error('Reject failed:', error)
  }
}

// Format timestamp for display
const formatTimestamp = (timestamp: string) => {
  const date = new Date(timestamp)
  return date.toLocaleString('en-GB', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
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
          <TextInput
            v-model="correlationIdFilter"
            placeholder="Correlation ID"
          />
          <TextInput
            v-model="nodeFilter"
            placeholder="Node"
          />
          <DropdownFilter
            v-model="topologyFilter"
            :options="topologyOptions"
            placeholder="All Topologies"
          />
          <TimeRangeFilterWithCustomRange v-model="timeRangeFilter" />
        </template>

        <!-- Custom cell templates -->
        <template #cell-topology="{ row }">
          <RouterLink
            :to="`/topologies/${row.topologyId}`"
            class="font-medium text-primary-600 hover:underline dark:text-primary-500"
          >
            {{ row.topology }}
          </RouterLink>
        </template>

        <template #cell-timestamp="{ value }">
          {{ formatTimestamp(value) }}
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

