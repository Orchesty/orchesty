<script setup lang="ts">
import { ref, onMounted, onActivated, onDeactivated, computed, watch } from 'vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import DateTimeRangeFilter from '@/components/ui/datagrid/DateTimeRangeFilter.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import Confirm from '@/components/ui/Confirm.vue'
import type { TrashItem, TrashQueryParams } from '@/types/trash'
import type { BulkAction } from '@/types/datagrid'
import type { TableColumn } from '@/types/dashboard'
import {
  fetchTrashItems,
  bulkApprove,
  bulkReject,
} from '@/services/trashService'
import { useDataGrid } from '@/composables/useDataGrid'
import { useToast } from '@/composables/useToast'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTabDataFreshness } from '@/composables/useTabDataFreshness'

interface Props {
  topologyId: string
  topologyName: string
  refreshKey?: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'open-drawer': [item: TrashItem]
}>()

// Toast notifications
const { showToast } = useToast()
const { isActive, isStale, markFresh, invalidate } = useTabDataFreshness()

// Topology and Node mappings
const { mappings, getNodeName } = useTopologyNodeMappings()
const { formatDateTime } = useDateFormat()

// State
const trashItems = ref<TrashItem[]>([])
const selectedRows = ref<Set<string>>(new Set())

// Filters
const searchFilter = ref('')
const correlationIdFilter = ref('')
const nodeFilter = ref<string | null>(null)
const dateTimeRange = ref<{ from: string | null; to: string | null }>({
  from: null,
  to: null,
})

// Node options filtered to the current topology
const nodeOptions = computed(() => {
  const options: { value: string | null; label: string }[] = [{ value: null, label: 'All Nodes' }]
  if (mappings.value) {
    const nodeIds = mappings.value.topologyTree[props.topologyId] || []
    for (const nodeId of nodeIds) {
      const name = mappings.value.nodes[nodeId] || nodeId
      options.push({ value: nodeId, label: name })
    }
  }
  return options
})


// Confirm modal states
const bulkApproveConfirmOpen = ref(false)
const bulkRejectConfirmOpen = ref(false)

// Count computed properties
const selectedCount = computed(() => selectedRows.value.size)

// Action handlers
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

// Table columns (without topology)
const columns: TableColumn[] = [
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

// Load data
const loadData = async () => {
  loading.value = true

  const params: TrashQueryParams = {
    page: currentPage.value,
    perPage: itemsPerPage.value,
    sortBy: sortField.value,
    sortOrder: sortDirection.value,
    topology: props.topologyId, // Auto-filter by current topology
  }

  if (searchFilter.value) {
    params.search = searchFilter.value
  }

  if (correlationIdFilter.value) {
    params.correlationId = correlationIdFilter.value
  }

  if (nodeFilter.value) {
    params.node = nodeFilter.value
  }

  if (dateTimeRange.value.from && dateTimeRange.value.to) {
    params.dateFrom = dateTimeRange.value.from
    params.dateTo = dateTimeRange.value.to
  }

  try {
    const response = await fetchTrashItems(params)
    trashItems.value = response.data
    totalItems.value = response.pagination.total
    totalPages.value = response.pagination.totalPages
    markFresh()
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
  filters: [searchFilter, correlationIdFilter, nodeFilter, dateTimeRange],
})

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

watch(() => props.refreshKey, () => {
  invalidate()
  if (isActive.value) loadData()
})

const openDrawer = (item: TrashItem) => {
  emit('open-drawer', item)
}


</script>

<template>
  <Card>
    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Failed Messages</h3>

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
      <template #filters>
        <TextInput
          v-model="searchFilter"
          placeholder="Search"
        />
        <TextInput
          v-model="correlationIdFilter"
          placeholder="Correlation ID"
        />
        <DropdownFilter
          v-model="nodeFilter"
          :options="nodeOptions"
          placeholder="All Nodes"
        />
        <DateTimeRangeFilter v-model="dateTimeRange" />
      </template>

      <!-- Custom cell templates -->
      <template #cell-timestamp="{ value }">
        {{ formatDateTime(value) }}
      </template>

      <template #cell-node="{ row }">
        <span class="text-sm text-gray-900 dark:text-white">
          {{ getNodeName(row.nodeId) }}
        </span>
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

    <!-- Confirm Modals for Bulk Actions -->
    <Confirm
      v-model="bulkApproveConfirmOpen"
      id="topology-bulk-approve-confirm"
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
      id="topology-bulk-reject-confirm"
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
  </Card>
</template>

