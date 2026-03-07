<script setup lang="ts">
import { ref, watch, computed, nextTick, onActivated, onDeactivated } from 'vue'
import ProcessesChart from './ProcessesChart.vue'
import ProcessAuditDrawer from './ProcessAuditDrawer.vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import SearchableDropdownFilter from '@/components/ui/datagrid/SearchableDropdownFilter.vue'
import DateTimeRangeFilter from '@/components/ui/datagrid/DateTimeRangeFilter.vue'
import type { Process, ProcessStatus } from '@/types/processes'
import type { ProcessFilter, TimeFilter, TableColumn, ProcessesChartData, ProcessesExternalFilters, HeatmapClickData } from '@/types/dashboard'
import type { QuickFilterOption, DropdownFilterOption } from '@/types/datagrid'
import { fetchProcesses } from '@/services/processesService'
import { fetchProcessesTotalCounts, fetchProcessesGraphData } from '@/services/dashboardService'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApi, formatDateTimeLocal } from '@/utils/timeRangeConverter'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useTabDataFreshness } from '@/composables/useTabDataFreshness'

interface Props {
  globalTimeFilter: TimeFilter
  heatmapFilter?: ProcessFilter
  externalFilters?: ProcessesExternalFilters
  refreshKey?: number
}

const props = withDefaults(defineProps<Props>(), {
  heatmapFilter: 'all',
})

const emit = defineEmits<{
  heatmapFilterChange: [filter: ProcessFilter]
}>()

// Use topology/node mappings composable
const { getTopologyName, getTopologyNameWithVersion, topologyNameMap, deduplicatedTopologyOptions, getTopologyIdsByName } = useTopologyNodeMappings()
const { formatDateTime } = useDateFormat()
const { isActive, isStale, markFresh, invalidate } = useTabDataFreshness()

// Drawer state
const drawerOpen = ref(false)
const selectedProcess = ref<Process | null>(null)

// Heatmap data
const processesChartData = ref<ProcessesChartData | null>(null)
const chartLoading = ref(true)

// Grid filters
const processes = ref<Process[]>([])
const statusFilter = ref<ProcessStatus>('all')
const topologyFilter = ref<string | null>(null)
const skipAutoLoad = ref(false)

const dateTimeRange = ref<{ from: string | null; to: string | null }>({
  from: null,
  to: null,
})

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
]

// Topology dropdown options (grouped by name -- all versions under one entry)
const topologyOptions = computed<DropdownFilterOption[]>(() => [
  { value: null, label: 'All Topologies' },
  ...deduplicatedTopologyOptions.value
])

const formatDuration = (ms: number): string => {
  if (ms < 1000) return `${Math.round(ms)}ms`
  const totalSeconds = ms / 1000
  if (totalSeconds < 60) return `${totalSeconds.toFixed(1)}s`
  const minutes = Math.floor(totalSeconds / 60)
  const secs = Math.round(totalSeconds % 60)
  if (minutes < 60) return `${minutes}m ${secs}s`
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return `${hours}h ${mins}m ${secs}s`
}

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

const handleAuditClick = (process: Process) => {
  console.log('Audit button clicked for process:', process)
  selectedProcess.value = process
  drawerOpen.value = true
  console.log('Drawer state:', { drawerOpen: drawerOpen.value, selectedProcess: selectedProcess.value })
}

const handleHeatmapClick = (data: HeatmapClickData) => {
  console.log('Heatmap clicked in ProcessesTab:', data)

  // Use exact slot boundaries from the heatmap
  // Pause auto-reload to set both filters atomically
  skipAutoLoad.value = true

  // Resolve topology ID to name (filter uses grouped names)
  topologyFilter.value = getTopologyName(data.topology)
  dateTimeRange.value = {
    from: formatDateTimeLocal(new Date(data.timeSlot)),
    to: formatDateTimeLocal(new Date(data.timeSlotEnd)),
  }

  // Resume and trigger single reload
  nextTick(() => {
    skipAutoLoad.value = false
    loadData()
  })
}

const handleProcessFilterChange = async (filter: ProcessFilter) => {
  emit('heatmapFilterChange', filter)
  // Chart data will reload via watch on props.heatmapFilter
}

const loadChartData = async () => {
  chartLoading.value = true
  try {
    const range = convertTimeFilterToDateTimeRange(props.globalTimeFilter)
    const dateFrom = formatDateTimeForApi(range.from) || ''
    const dateTo = formatDateTimeForApi(range.to) || ''

    const totals = await fetchProcessesTotalCounts(dateFrom, dateTo)
    const chartData = await fetchProcessesGraphData(props.heatmapFilter, dateFrom, dateTo, 40)

    processesChartData.value = {
      ...chartData,
      totalProcesses: totals.totalProcesses,
      failedProcesses: totals.failedProcesses,
    }
  } catch (error) {
    console.error('Error loading processes chart data:', error)
  } finally {
    chartLoading.value = false
  }
}

watch(
  () => props.globalTimeFilter,
  (newFilter) => {
    const range = convertTimeFilterToDateTimeRange(newFilter)
    dateTimeRange.value = {
      from: range.from,
      to: range.to,
    }
    invalidate()
    if (isActive.value) {
      loadChartData()
    }
  },
  { immediate: true }
)

watch(
  () => props.heatmapFilter,
  () => {
    invalidate()
    if (isActive.value) loadChartData()
  }
)

watch(() => props.refreshKey, () => {
  invalidate()
  loadData()
  loadChartData()
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

onActivated(() => {
  isActive.value = true
  if (isStale()) {
    loadData()
    loadChartData()
  }
})

onDeactivated(() => {
  isActive.value = false
})

</script>

<template>
  <div>
    <!-- Processes Chart -->
    <div class="mb-6">
      <Card v-if="chartLoading" class="flex items-center justify-center p-12">
        <div class="text-center">
          <svg
            class="mx-auto h-12 w-12 animate-spin text-primary-600"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          <p class="mt-4 text-gray-500 dark:text-gray-400">Loading chart...</p>
        </div>
      </Card>
      <ProcessesChart
        v-else-if="processesChartData"
        chart-id="processes"
        :total-processes="processesChartData.totalProcesses || 0"
        :total-failed="processesChartData.failedProcesses || 0"
        :time-range="processesChartData.timeRange || ''"
        :filter="props.heatmapFilter"
        :series="processesChartData.series"
        :x-categories="processesChartData.xCategories || []"
        :y-label-map="topologyNameMap"
        @filter-change="handleProcessFilterChange"
        @heatmap-click="handleHeatmapClick"
      />
    </div>

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
        </template>

        <!-- Custom Cells -->
        <template #cell-topologyId="{ value }">
          <span class="whitespace-nowrap font-medium text-gray-900 dark:text-white">
            {{ getTopologyNameWithVersion(value) }}
          </span>
        </template>

        <template #cell-startTime="{ value }">
          <span class="whitespace-nowrap">{{ formatDateTime(value) }}</span>
        </template>

        <template #cell-duration="{ value }">
          <span class="whitespace-nowrap">{{ formatDuration(value) }}</span>
        </template>

        <template #cell-status="{ value }">
          <span
            :class="[
              'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
              value === 'completed'
                ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-300'
                : value === 'running'
                ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-300'
                : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-300',
            ]"
          >
            {{ value.charAt(0).toUpperCase() + value.slice(1) }}
          </span>
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
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
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
            <button
              type="button"
              title="Get payload"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg
                class="h-5 w-5"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z"
                  clip-rule="evenodd"
                />
              </svg>
              <span class="sr-only">Get payload</span>
            </button>
          </div>
        </template>
      </DataGrid>
    </Card>

    <!-- Process Audit Drawer - Always render to ensure Flowbite initialization -->
    <ProcessAuditDrawer v-model="drawerOpen" :process="selectedProcess" />
  </div>
</template>

