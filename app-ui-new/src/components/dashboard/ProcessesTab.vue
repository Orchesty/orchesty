<script setup lang="ts">
import { ref, watch, computed, nextTick, onMounted, onActivated, onDeactivated } from 'vue'
import ProcessAuditDrawer from './ProcessAuditDrawer.vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import SearchableDropdownFilter from '@/components/ui/datagrid/SearchableDropdownFilter.vue'
import GridLink from '@/components/ui/datagrid/GridLink.vue'
import DateTimeRangeFilter from '@/components/ui/datagrid/DateTimeRangeFilter.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { Process, ProcessStatus } from '@/types/processes'
import type { TimeFilter, TableColumn, ProcessesExternalFilters } from '@/types/dashboard'
import type { QuickFilterOption, DropdownFilterOption } from '@/types/datagrid'
import { fetchProcesses } from '@/services/processesService'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApi, formatDateTimeLocal } from '@/utils/timeRangeConverter'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useTabDataFreshness } from '@/composables/useTabDataFreshness'

interface Props {
  timeFilter?: TimeFilter
  externalFilters?: ProcessesExternalFilters
  refreshKey?: number
}

const props = withDefaults(defineProps<Props>(), {
  timeFilter: '24h',
  refreshKey: 0,
})

const refreshing = ref(false)

// Use topology/node mappings composable
const { getTopologyName, getTopologyNameWithVersion, deduplicatedTopologyOptions, getTopologyIdsByName } = useTopologyNodeMappings()
const { formatDateTime, formatDurationMs } = useDateFormat()
const { isActive, isStale, markFresh, invalidate } = useTabDataFreshness()

// Drawer state
const drawerOpen = ref(false)
const selectedProcess = ref<Process | null>(null)

// Grid filters
const processes = ref<Process[]>([])
const statusFilter = ref<ProcessStatus>('all')
const topologyFilter = ref<string | null>(null)

const initialRange = convertTimeFilterToDateTimeRange(props.timeFilter)
const hasExternalFilters = !!(props.externalFilters?.topology || props.externalFilters?.timeRange)
const skipAutoLoad = ref(hasExternalFilters)

const dateTimeRange = ref<{ from: string | null; to: string | null }>({
  from: props.externalFilters?.timeRange?.from ?? initialRange.from,
  to: props.externalFilters?.timeRange?.to ?? null,
})

if (props.externalFilters?.topology) {
  topologyFilter.value = getTopologyName(props.externalFilters.topology)
}

// Table columns
const columns: TableColumn[] = [
  { key: 'topologyId', label: 'Topology', sortable: false },
  { key: 'startTime', label: 'Start time', sortable: true },
  { key: 'duration', label: 'Duration', sortable: true },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'errorMessage', label: 'Error Message', sortable: false },
  { key: 'actions', label: '', className: 'text-right' },
]

// Quick filter options
const quickFilterOptions: QuickFilterOption[] = [
  { value: 'all', label: 'All' },
  { value: 'completed', label: 'Completed' },
  { value: 'running', label: 'Running' },
  { value: 'failed', label: 'Failed' },
  { value: 'terminated', label: 'Terminated' },
]

// Topology dropdown options (grouped by name -- all versions under one entry)
const topologyOptions = computed<DropdownFilterOption[]>(() => [
  { value: null, label: 'All Topologies' },
  ...deduplicatedTopologyOptions.value
])

// Load data function
const loadData = async () => {
  loading.value = true

  try {
    // Resolve topology name to all version IDs for filtering
    const topologyIds = topologyFilter.value
      ? getTopologyIdsByName(topologyFilter.value)
      : undefined

    const response = await fetchProcesses({
      status: statusFilter.value,
      topologyIds: topologyIds && topologyIds.length > 0 ? topologyIds : undefined,
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
  filters: [statusFilter, topologyFilter, dateTimeRange],
  skipAutoLoad,
})

if (hasExternalFilters) {
  nextTick(() => {
    skipAutoLoad.value = false
  })
}

const handleRefresh = () => {
  refreshing.value = true
  invalidate()
  loadData()
  setTimeout(() => { refreshing.value = false }, 800)
}

const handleAuditClick = (process: Process) => {
  selectedProcess.value = process
  drawerOpen.value = true
}

watch(
  () => props.timeFilter,
  (newFilter) => {
    const range = convertTimeFilterToDateTimeRange(newFilter)
    dateTimeRange.value = {
      from: range.from,
      to: null,
    }
    invalidate()
  },
)

watch(() => props.refreshKey, () => {
  invalidate()
  loadData()
})

watch(
  () => props.externalFilters,
  (filters) => {
    if (!filters) return
    if (!filters.topology && !filters.timeRange) return

    skipAutoLoad.value = true

    if (filters.topology) {
      topologyFilter.value = getTopologyName(filters.topology)
    }

    if (filters.timeRange) {
      dateTimeRange.value = {
        from: filters.timeRange.from,
        to: filters.timeRange.to,
      }
    }

    nextTick(() => {
      skipAutoLoad.value = false
      loadData()
    })
  },
  { deep: true }
)

onMounted(() => {
  loadData()
})

onActivated(() => {
  isActive.value = true
  if (isStale()) {
    loadData()
  }
})

onDeactivated(() => {
  isActive.value = false
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
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @sort="handleSort"
      >
        <!-- Quick Filters (left) -->
        <template #quick-filters>
          <QuickFilter
            v-model="statusFilter"
            name="processes-filter"
            label="Show only:"
            :options="quickFilterOptions"
          />
        </template>

        <!-- Regular Filters (right) -->
        <template #filters>
          <!-- Topology Dropdown -->
          <SearchableDropdownFilter
            v-model="topologyFilter"
            :options="topologyOptions"
            placeholder="All Topologies"
            search-placeholder="Search topologies..."
            min-width="min-w-56"
          />

          <!-- DateTime Range Filter -->
          <DateTimeRangeFilter v-model="dateTimeRange" />

          <!-- Refresh -->
          <button
            type="button"
            title="Refresh"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:text-gray-900 focus:outline-hidden dark:text-gray-500 dark:hover:text-white"
            @click="handleRefresh"
          >
            <svg
              class="h-5 w-5 transition-transform duration-500"
              :class="{ 'animate-spin': refreshing }"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M1 4v6h6M23 20v-6h-6" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15" />
            </svg>
            <span class="sr-only">Refresh</span>
          </button>
        </template>

        <!-- Custom Cells -->
        <template #cell-topologyId="{ value }">
          <GridLink :to="{ name: 'topology-detail', params: { id: value } }">
            {{ getTopologyNameWithVersion(value) }}
          </GridLink>
        </template>

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
              @click="handleAuditClick(row as Process)"
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

