<script setup lang="ts">
import { ref, onMounted, onActivated, onDeactivated, watch } from 'vue'
import ProcessAuditDrawer from '@/components/dashboard/ProcessAuditDrawer.vue'
import Card from '@/components/ui/Card.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import DateTimeRangeFilter from '@/components/ui/datagrid/DateTimeRangeFilter.vue'
import type { Process, ProcessStatus } from '@/types/processes'
import type { TableColumn } from '@/types/dashboard'
import type { QuickFilterOption } from '@/types/datagrid'
import { fetchProcesses } from '@/services/processesService'
import { formatDateTimeForApi } from '@/utils/timeRangeConverter'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTabDataFreshness } from '@/composables/useTabDataFreshness'

interface Props {
  topologyId: string
  topologyName: string
  refreshKey?: number
}

const props = defineProps<Props>()
const { formatDateTime, formatDurationMs } = useDateFormat()
const { isActive, isStale, markFresh, invalidate } = useTabDataFreshness()

// Drawer state
const drawerOpen = ref(false)
const selectedProcess = ref<Process | null>(null)

// Grid filters
const processes = ref<Process[]>([])
const statusFilter = ref<ProcessStatus>('all')

// Local datetime range filters
const dateTimeRange = ref<{ from: string | null; to: string | null }>({
  from: null,
  to: null,
})

// Table columns (without topology)
const columns: TableColumn[] = [
  { key: 'startTime', label: 'Start time', sortable: true },
  { key: 'duration', label: 'Duration', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'errorMessage', label: 'Error Message', sortable: false },
  { key: 'actions', label: '', className: 'text-right w-16' },
]

// Quick filter options
const quickFilterOptions: QuickFilterOption[] = [
  { value: 'all', label: 'All' },
  { value: 'completed', label: 'Completed' },
  { value: 'running', label: 'Running' },
  { value: 'failed', label: 'Failed' },
  { value: 'terminated', label: 'Terminated' },
]

// Load data function
const loadData = async () => {
  loading.value = true

  try {
    const response = await fetchProcesses({
      status: statusFilter.value,
      topology: props.topologyId, // Auto-filter by current topology
      dateFrom: formatDateTimeForApi(dateTimeRange.value.from) || undefined,
      dateTo: formatDateTimeForApi(dateTimeRange.value.to) || undefined,
      page: currentPage.value,
      limit: itemsPerPage.value,
      sort: sortField.value,
      order: sortDirection.value,
    })

    processes.value = response.data
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems
    markFresh()
  } catch (error) {
    console.error('Error loading processes:', error)
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
  defaultSort: { field: 'startTime', direction: 'desc' },
  onDataLoad: loadData,
  filters: [statusFilter, dateTimeRange],
})

const handleAuditClick = (row: Record<string, unknown>) => {
  const process = row as unknown as Process
  selectedProcess.value = process
  drawerOpen.value = true
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

watch(() => props.refreshKey, () => {
  invalidate()
  if (isActive.value) loadData()
})
</script>

<template>
  <div>
    <!-- Processes Grid Card -->
    <Card>
      <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Processes</h3>

      <DataGrid
        :columns="columns"
        :data="processes"
        :loading="loading"
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalItems"
        :items-per-page="itemsPerPage"
        :sort-field="sortField"
        :sort-direction="sortDirection"
        show-refresh
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @sort="handleSort"
        @refresh="loadData"
      >
        <!-- Quick Filters (left) -->
        <template #quick-filters>
          <QuickFilter
            v-model="statusFilter"
            name="topology-processes-filter"
            label="Show only:"
            :options="quickFilterOptions"
          />
        </template>

        <!-- Regular Filters (right) -->
        <template #filters>
          <!-- DateTime Range Filter -->
          <DateTimeRangeFilter v-model="dateTimeRange" />
        </template>

        <!-- Custom Cells -->
        <template #cell-startTime="{ value }">
          <span class="whitespace-nowrap">{{ formatDateTime(value) }}</span>
        </template>

        <template #cell-duration="{ value }">
          <span class="whitespace-nowrap">{{ formatDurationMs(value) }}</span>
        </template>

        <template #cell-status="{ value }">
          <StatusBadge :variant="value === 'completed' ? 'green' : value === 'running' ? 'blue' : value === 'terminated' ? 'yellow' : 'red'">
            {{ value.charAt(0).toUpperCase() + value.slice(1) }}
          </StatusBadge>
        </template>

        <template #cell-errorMessage="{ value }">
          <span
            v-if="value"
            class="break-words text-xs"
          >
            {{ value }}
          </span>
          <span v-else class="text-xs">-</span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button
              type="button"
              title="Audit"
              @click="handleAuditClick(row)"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
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
              <span class="sr-only">Audit</span>
            </button>
          </div>
        </template>
      </DataGrid>
    </Card>

    <!-- Process Audit Drawer - Always render to ensure Flowbite initialization -->
    <ProcessAuditDrawer v-model="drawerOpen" :process="selectedProcess" />
  </div>
</template>

