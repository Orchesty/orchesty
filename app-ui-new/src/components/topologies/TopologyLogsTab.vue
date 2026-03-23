<script setup lang="ts">
import { ref, computed, onMounted, onActivated, onDeactivated, watch } from 'vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import DateTimeRangeFilter from '@/components/ui/datagrid/DateTimeRangeFilter.vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import LogDetailModal from '@/components/logs/LogDetailModal.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { LogEntry, LogQueryParams, LogSeverity } from '@/types/logs'
import type { TableColumn } from '@/types/dashboard'
import { fetchLogs } from '@/services/logsService'
import { useDataGrid } from '@/composables/useDataGrid'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTabDataFreshness } from '@/composables/useTabDataFreshness'

interface Props {
  topologyId: string
  topologyName: string
  refreshKey?: number
}

const props = defineProps<Props>()
const { isActive, isStale, markFresh, invalidate } = useTabDataFreshness()

// State
const logs = ref<LogEntry[]>([])

// Drawer state
const drawerOpen = ref(false)
const selectedLog = ref<LogEntry | null>(null)

// Topology and Node mappings
const { mappings, getNodeName } = useTopologyNodeMappings()
const { formatDateTime } = useDateFormat()

// Filters
const searchFilter = ref('')
const correlationIdFilter = ref('')
const severityFilter = ref<LogSeverity | null>(null)
const nodeFilter = ref<string | null>(null)
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

// Table columns (without topology)
const columns: TableColumn[] = [
  { key: 'timestamp', label: 'Timestamp', sortable: true },
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
    topology: props.topologyId, // Auto-filter by current topology
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

  if (nodeFilter.value) {
    params.node = nodeFilter.value
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
    markFresh()
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
  filters: [searchFilter, correlationIdFilter, severityFilter, nodeFilter, dateTimeRange],
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
      show-refresh
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
      @sort="handleSort"
      @refresh="loadData"
    >
      <template #filters>
        <TextInput
          v-model="searchFilter"
          placeholder="Search..."
          width="w-80"
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
        <DropdownFilter
          v-model="nodeFilter"
          :options="nodeOptions"
          placeholder="All Nodes"
        />
        <DateTimeRangeFilter v-model="dateTimeRange" />
      </template>

      <!-- Custom cell templates -->
      <template #cell-timestamp="{ value }">
        <span class="whitespace-nowrap">{{ formatDateTime(value) }}</span>
      </template>

      <template #cell-node="{ value }">
        <span class="whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ getNodeName(value) }}</span>
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

    <!-- Log Detail Modal -->
    <LogDetailModal
      v-model="drawerOpen"
      :log="selectedLog"
    />
  </Card>
</template>

