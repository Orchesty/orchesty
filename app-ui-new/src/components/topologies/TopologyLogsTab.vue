<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import TimeRangeFilterWithCustomRange from '@/components/ui/TimeRangeFilterWithCustomRange.vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import LogDetailDrawer from '@/components/logs/LogDetailDrawer.vue'
import type { LogEntry, LogQueryParams, LogSeverity } from '@/types/logs'
import type { TableColumn } from '@/types/dashboard'
import { fetchLogs } from '@/services/logsService'
import { useDataGrid } from '@/composables/useDataGrid'

interface Props {
  topologyId: string
  topologyName: string
}

const props = defineProps<Props>()

// State
const logs = ref<LogEntry[]>([])

// Drawer state
const drawerOpen = ref(false)
const selectedLog = ref<LogEntry | null>(null)

// Filters
const searchFilter = ref('')
const timeMarginFilter = ref('')
const severityFilter = ref<LogSeverity | null>(null)
const timeRangeFilter = ref('this-month')

// Severity options for dropdown
const severityOptions = ref<{ value: LogSeverity | null; label: string }[]>([
  { value: null, label: 'All Severities' },
  { value: 'error', label: 'Error' },
  { value: 'warning', label: 'Warning' },
  { value: 'info', label: 'Info' },
  { value: 'debug', label: 'Debug' },
])

// Table columns (without topology)
const columns: TableColumn[] = [
  { key: 'timestamp', label: 'Timestamp', sortable: true },
  { key: 'node', label: 'Node', sortable: false },
  { key: 'nodeId', label: 'Node ID', sortable: false },
  { key: 'severity', label: 'Severity', sortable: true },
  { key: 'message', label: 'Message', sortable: false },
  { key: 'actions', label: '', className: 'text-right' },
]

// Format timestamp for display
const formatTimestamp = (timestamp: string): string => {
  const date = new Date(timestamp)
  return date.toLocaleString('en-GB', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

// Get severity badge classes
const getSeverityClass = (severity: LogSeverity): string => {
  const classes = {
    error: 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-300',
    warning: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-300',
    info: 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-300',
    debug: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
  }
  return classes[severity] || classes.info
}

// Open drawer with selected log
const openDrawer = (log: LogEntry) => {
  selectedLog.value = log
  drawerOpen.value = true
}

// Load data function
async function loadData() {
  loading.value = true
  
  const params: LogQueryParams = {
    page: currentPage.value,
    perPage: itemsPerPage.value,
    sortBy: sortField.value,
    sortOrder: sortDirection.value,
    topology: props.topologyName, // Auto-filter by current topology
  }

  if (searchFilter.value) {
    params.search = searchFilter.value
  }

  if (timeMarginFilter.value) {
    const margin = parseInt(timeMarginFilter.value, 10)
    if (!isNaN(margin)) {
      params.timeMargin = margin
    }
  }

  if (severityFilter.value) {
    params.severity = severityFilter.value
  }

  if (timeRangeFilter.value) {
    params.timeRange = timeRangeFilter.value
  }

  try {
    const response = await fetchLogs(params)
    logs.value = response.data
    totalItems.value = response.pagination.total
    totalPages.value = response.pagination.totalPages
  } catch (error) {
    console.error('Failed to load logs:', error)
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
  filters: [searchFilter, timeMarginFilter, severityFilter, timeRangeFilter],
})

// Load initial data
onMounted(async () => {
  await loadData()
})
</script>

<template>
  <Card>
    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Logs</h3>

    <DataGrid
      :columns="columns"
      :data="logs"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      :total-items="totalItems"
      :items-per-page="itemsPerPage"
      :sort-field="sortField"
      :sort-direction="sortDirection"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
      @sort="handleSort"
    >
      <template #filters>
        <TextInput
          v-model="searchFilter"
          placeholder="Search..."
          width="w-80"
        />
        <TextInput
          v-model="timeMarginFilter"
          type="number"
          placeholder="Time Margin"
          width="w-32"
        />
        <DropdownFilter
          v-model="severityFilter"
          :options="severityOptions"
          placeholder="All Severities"
        />
        <TimeRangeFilterWithCustomRange v-model="timeRangeFilter" />
      </template>

      <!-- Custom cell templates -->
      <template #cell-timestamp="{ value }">
        <span class="whitespace-nowrap">{{ formatTimestamp(value) }}</span>
      </template>

      <template #cell-node="{ value }">
        <span class="whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ value }}</span>
      </template>

      <template #cell-nodeId="{ value }">
        <span class="font-mono text-xs text-gray-900 dark:text-white">{{ value }}</span>
      </template>

      <template #cell-severity="{ value }">
        <span
          :class="[
            'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
            getSeverityClass(value),
          ]"
        >
          {{ value.charAt(0).toUpperCase() + value.slice(1) }}
        </span>
      </template>

      <template #cell-message="{ value }">
        <span class="text-xs">{{ value }}</span>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center justify-end gap-1">
          <button
            type="button"
            title="View details"
            class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            @click="openDrawer(row as LogEntry)"
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
          <CopyValue
            :value="row.correlationId"
            hide-value
            title="Copy Correlation ID"
          />
        </div>
      </template>
    </DataGrid>

    <!-- Log Detail Drawer -->
    <LogDetailDrawer
      v-model="drawerOpen"
      :log="selectedLog"
    />
  </Card>
</template>

