<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import SearchableDropdownFilter from '@/components/ui/datagrid/SearchableDropdownFilter.vue'
import DateTimeRangeFilter from '@/components/ui/datagrid/DateTimeRangeFilter.vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import LogDetailModal from '@/components/logs/LogDetailModal.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { LogEntry, LogQueryParams, LogSeverity } from '@/types/logs'
import type { TableColumn } from '@/types/dashboard'
import { fetchLogs } from '@/services/logsService'
import { useDateFormat } from '@/composables/useDateFormat'
import { useDataGrid } from '@/composables/useDataGrid'
import { useTopologyNodeFilter } from '@/composables/useTopologyNodeFilter'

const {
  topologyFilter,
  nodeFilter,
  topologyOptions,
  nodeOptions,
  getTopologyName,
  getNodeName,
  getNodeIdsByName,
} = useTopologyNodeFilter()
const { formatDateTime } = useDateFormat()

// State
const logs = ref<LogEntry[]>([])

// Drawer state
const drawerOpen = ref(false)
const selectedLog = ref<LogEntry | null>(null)

// Filters
const searchFilter = ref('')
const correlationIdFilter = ref('')
const severityFilter = ref<LogSeverity | null>(null)
const dateTimeRange = ref<{ from: string | null; to: string | null }>({
  from: null,
  to: null,
})

// Severity options for dropdown
const severityOptions = ref<{ value: LogSeverity | null; label: string }[]>([
  { value: null, label: 'All Severities' },
  { value: 'error', label: 'Error' },
  { value: 'warning', label: 'Warning' },
  { value: 'info', label: 'Info' },
  { value: 'debug', label: 'Debug' },
])

// Table columns
const columns: TableColumn[] = [
  { key: 'timestamp', label: 'Timestamp', sortable: true },
  { key: 'topology', label: 'Topology', sortable: false },
  { key: 'node', label: 'Node', sortable: false },
  { key: 'nodeId', label: 'Node ID', sortable: false },
  { key: 'severity', label: 'Severity', sortable: false },
  { key: 'message', label: 'Message', sortable: false },
  { key: 'actions', label: '', className: 'text-right w-16' },
]

const severityVariant: Record<LogSeverity, 'red' | 'yellow' | 'blue' | 'gray'> = {
  error: 'red',
  warning: 'yellow',
  info: 'blue',
  debug: 'gray',
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
  }

  if (searchFilter.value) {
    params.search = searchFilter.value
  }

  if (correlationIdFilter.value) {
    params.correlationId = correlationIdFilter.value
  }

  if (severityFilter.value) {
    params.severity = severityFilter.value
  }

  if (topologyFilter.value) {
    params.topology = topologyFilter.value
  }

  if (nodeFilter.value) {
    const nodeIds = getNodeIdsByName(nodeFilter.value)
    params.node = nodeIds.length > 0 ? nodeIds : [nodeFilter.value]
  }

  if (dateTimeRange.value.from && dateTimeRange.value.to) {
    params.dateFrom = dateTimeRange.value.from
    params.dateTo = dateTimeRange.value.to
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
  filters: [searchFilter, correlationIdFilter, severityFilter, topologyFilter, nodeFilter, dateTimeRange],
})

onMounted(() => {
  loadData()
})
</script>

<template>
  <main class="h-full overflow-y-auto"><div class="px-4 pb-4 pt-6">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Logs</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        View logs from all topologies
      </p>
    </div>

    <!-- Logs Table Card -->
    <Card>
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
        show-refresh
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @sort="handleSort"
        @refresh="loadData"
      >
        <template #filters>
          <SearchInput
            v-model="searchFilter"
            placeholder="Search..."
            mode="server"
          />
          <TextInput
            v-model="correlationIdFilter"
            placeholder="Correlation ID"
          />
          <DropdownFilter
            v-model="severityFilter"
            :options="severityOptions"
            placeholder="All Severities"
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
        <template #cell-timestamp="{ value }">
          <span class="whitespace-nowrap">{{ formatDateTime(value) }}</span>
        </template>

        <template #cell-topology="{ row }">
          <RouterLink
            :to="`/topologies/${row.topologyId}`"
            class="whitespace-nowrap font-medium text-gray-900 hover:underline dark:text-white"
          >
            {{ getTopologyName(row.topologyId) }}
          </RouterLink>
        </template>

        <template #cell-node="{ row }">
          <span class="whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ getNodeName(row.nodeId) }}</span>
        </template>

        <template #cell-nodeId="{ value }">
          <span class="font-mono text-xs text-gray-900 dark:text-white">{{ value }}</span>
        </template>

        <template #cell-severity="{ value }">
          <StatusBadge :variant="severityVariant[value as LogSeverity] || 'blue'">
            {{ value.charAt(0).toUpperCase() + value.slice(1) }}
          </StatusBadge>
        </template>

        <template #cell-message="{ value }">
          <span class="text-xs">{{ value }}</span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button
              type="button"
              title="View details"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
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
    </Card>

    <!-- Log Detail Modal -->
    <LogDetailModal
      v-model="drawerOpen"
      :log="selectedLog"
    />
  </div></main>
</template>

