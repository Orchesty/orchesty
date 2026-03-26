<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import TimeFilter from '@/components/ui/TimeFilter.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import ConnectorMetricDetailModal from '@/components/dashboard/ConnectorMetricDetailModal.vue'
import type { Connector, ConnectorDetail, ConnectorErrorRecord } from '@/types/connectors'
import type { TimeFilter as TimeFilterType, TableColumn } from '@/types/dashboard'
import type { ActionConfig } from '@/types/datagrid'
import { fetchConnectorDetail, fetchConnectorErrorRecords, fetchConnectorChartData } from '@/services/connectorsService'
import { useApexChart } from '@/composables/useApexChart'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useDateFormat } from '@/composables/useDateFormat'
import GridLink from '@/components/ui/datagrid/GridLink.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'

interface Props {
  modelValue: boolean
  connector: Connector | null
  timeFilter: TimeFilterType
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

// Use topology/node mappings composable
const { getTopologyName, getApplicationNameByNodeId, getNodeName, getNodeIdsByName } = useTopologyNodeMappings()
const { formatDurationMs } = useDateFormat()

// Local time filter (independent from global)
const localTimeFilter = ref<TimeFilterType>(props.timeFilter)

// Data state
const connectorDetail = ref<ConnectorDetail | null>(null)
const errorRecords = ref<ConnectorErrorRecord[]>([])
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const itemsPerPage = ref(10)
const loading = ref(false)

// Chart data
const chartData = ref<{ categories: number[]; errors400: number[]; errors500: number[] } | null>(null)

// Chart element
const chartElement = ref<HTMLElement | null>(null)

// Initialize chart with composable
const { initChart, isDarkMode, destroyChart } = useApexChart({
  onDarkModeChange: () => {
    // Re-create chart on dark mode change
    if (chartElement.value && chartData.value) {
      renderChart()
    }
  },
})

const getChartOptions = () => {
  if (!chartData.value) return null

  const cats = chartData.value.categories

  return {
    series: [
      {
        name: '400 Errors',
        data: chartData.value.errors400.map((v, i) => [cats[i], v]),
        color: '#F59E0B',
      },
      {
        name: '500 Errors',
        data: chartData.value.errors500.map((v, i) => [cats[i], v]),
        color: '#EF4444',
      },
    ],
    chart: {
      type: 'area',
      height: 300,
      toolbar: { show: false },
      zoom: { enabled: false },
      background: 'transparent',
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: {
      type: 'gradient',
      gradient: { opacityFrom: 0.5, opacityTo: 0.1 },
    },
    xaxis: {
      type: 'datetime',
      labels: {
        datetimeUTC: false,
        style: { colors: isDarkMode.value ? '#9CA3AF' : '#6B7280' },
      },
      tickAmount: 8,
    },
    yaxis: {
      labels: {
        formatter: (value: number) => Math.floor(value).toString(),
        style: { colors: isDarkMode.value ? '#9CA3AF' : '#6B7280' },
      },
    },
    legend: {
      position: 'top',
      horizontalAlign: 'right',
      labels: { colors: isDarkMode.value ? '#9CA3AF' : '#6B7280' },
    },
    grid: {
      borderColor: isDarkMode.value ? '#374151' : '#E5E7EB',
    },
    tooltip: {
      theme: isDarkMode.value ? 'dark' : 'light',
      x: {
        format: 'd. M. yyyy  HH:mm',
      },
    },
  }
}

// Render chart
const renderChart = () => {
  if (!chartElement.value || !chartData.value) return

  const options = getChartOptions()
  if (options) {
    destroyChart()
    initChart(chartElement.value, options)
  }
}

// Watch for chartData changes and render chart
watch(chartData, () => {
  if (chartData.value && chartElement.value) {
    renderChart()
  }
})

// Computed properties
const lastRequestStatusColor = computed(() => {
  if (!connectorDetail.value) return 'gray'
  const status = connectorDetail.value.lastRequestStatus
  if (status >= 200 && status < 300) return 'green'
  if (status >= 400 && status < 500) return 'yellow'
  return 'red'
})

// Load data
const loadData = async () => {
  if (!props.connector) return

  loading.value = true

  const nodeIds = getNodeIdsByName(getNodeName(props.connector.id))
  const resolvedIds = nodeIds.length > 0 ? nodeIds : [props.connector.id]

  try {
    const detail = await fetchConnectorDetail(resolvedIds, localTimeFilter.value)
    connectorDetail.value = detail

    const chart = await fetchConnectorChartData(resolvedIds, localTimeFilter.value, 20)
    chartData.value = chart

    const apiSortField = sortField.value === 'timestamp' ? 'created' : sortField.value
    const records = await fetchConnectorErrorRecords(
      resolvedIds,
      localTimeFilter.value,
      currentPage.value,
      itemsPerPage.value,
      apiSortField,
      sortDirection.value
    )
    errorRecords.value = records.data
    totalPages.value = records.meta.totalPages
    totalItems.value = records.meta.totalItems
  } catch (error) {
    console.error('Error loading connector detail:', error)
  } finally {
    loading.value = false
  }
}

// Watch for changes
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue && props.connector) {
      // Reset to global time filter when opening
      localTimeFilter.value = props.timeFilter
      currentPage.value = 1
      loadData()
    }
  },
)

watch(localTimeFilter, () => {
  currentPage.value = 1
  loadData()
})

watch(currentPage, () => {
  loadData()
})

// Sort state
const sortField = ref('timestamp')
const sortDirection = ref<'asc' | 'desc'>('desc')

// Pagination handlers
const handlePageChange = (page: number) => {
  currentPage.value = page
}

const handlePerPageChange = (perPage: number) => {
  itemsPerPage.value = perPage
  currentPage.value = 1
}

const handleSort = (config: { field: string; direction: 'asc' | 'desc' }) => {
  sortField.value = config.field
  sortDirection.value = config.direction
  currentPage.value = 1
  loadData()
}

// Metric detail modal state
const metricDetailOpen = ref(false)
const selectedRecord = ref<ConnectorErrorRecord | null>(null)

const openMetricDetail = (record: ConnectorErrorRecord) => {
  selectedRecord.value = record
  metricDetailOpen.value = true
}

// Table columns for error records
const errorRecordsColumns: TableColumn[] = [
  { key: 'timestamp', label: 'Timestamp', sortable: true, className: 'w-48' },
  { key: 'topology', label: 'Topology' },
  { key: 'code', label: 'Code' },
  { key: 'message', label: 'Error Message' },
]

const errorRecordActions: ActionConfig[] = [
  {
    icon: 'search',
    title: 'View detail',
    onClick: (row) => openMetricDetail(row as ConnectorErrorRecord),
  },
]
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="connector-detail-drawer"
    label="Connector Details"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Header Actions Slot -->
    <template #header-actions>
      <div v-if="connector" class="flex items-center justify-between">
        <div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ getNodeName(connector.id) }}</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ getApplicationNameByNodeId(connector.id) }}</p>
        </div>

        <!-- Local Time Filter -->
        <TimeFilter v-model="localTimeFilter" />
      </div>
    </template>

    <!-- Main Content -->
    <div v-if="connectorDetail" class="space-y-8">
      <!-- Chart -->
      <div>
        <div ref="chartElement" id="connector-error-chart"></div>
      </div>

      <!-- Summary Stats -->
      <div class="grid grid-cols-3 gap-3">
        <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-700">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ connectorDetail.totalRequests.toLocaleString() }}
          </div>
          <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">Total requests</div>
        </div>
        <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-700">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatDurationMs(connectorDetail.avgRequestTime) }}
          </div>
          <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">Avg request time</div>
        </div>
        <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-700">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatDurationMs(connectorDetail.lastRequestTime) }}
          </div>
          <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">Last request time</div>
        </div>
        <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-700">
          <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
            {{ connectorDetail.errors400 }}
          </div>
          <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">400 Errors</div>
        </div>
        <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-700">
          <div class="text-2xl font-bold text-red-600 dark:text-red-400">
            {{ connectorDetail.errors500 }}
          </div>
          <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">500 Errors</div>
        </div>
        <div
          :class="[
            'rounded-lg p-3 text-center',
            lastRequestStatusColor === 'green'
              ? 'bg-green-100 dark:bg-green-800'
              : lastRequestStatusColor === 'yellow'
              ? 'bg-yellow-100 dark:bg-yellow-800'
              : 'bg-red-100 dark:bg-red-800',
          ]"
        >
          <div
            :class="[
              'text-2xl font-bold',
              lastRequestStatusColor === 'green'
                ? 'text-green-700 dark:text-green-300'
                : lastRequestStatusColor === 'yellow'
                ? 'text-yellow-700 dark:text-yellow-300'
                : 'text-red-700 dark:text-red-300',
            ]"
          >
            {{ connectorDetail.lastRequestStatus }}
          </div>
          <div
            :class="[
              'mt-1 text-sm font-medium',
              lastRequestStatusColor === 'green'
                ? 'text-green-600 dark:text-green-400'
                : lastRequestStatusColor === 'yellow'
                ? 'text-yellow-600 dark:text-yellow-400'
                : 'text-red-600 dark:text-red-400',
            ]"
          >
            Last status
          </div>
        </div>
      </div>

      <!-- Error Records Table -->
      <div>
        <div class="mb-3">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Error Records</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Recent error occurrences and details
          </p>
        </div>

        <DataGrid
          :columns="errorRecordsColumns"
          :data="errorRecords"
          :loading="loading"
          :current-page="currentPage"
          :total-pages="totalPages"
          :total-items="totalItems"
          :items-per-page="itemsPerPage"
          :sort-field="sortField"
          :sort-direction="sortDirection"
          :actions="errorRecordActions"
          @page-change="handlePageChange"
          @per-page-change="handlePerPageChange"
          @sort="handleSort"
        >
          <!-- Custom cell for timestamp -->
          <template #cell-timestamp="{ value }">
            <span class="whitespace-nowrap font-medium text-gray-900 dark:text-white">
              {{ value }}
            </span>
          </template>

          <!-- Custom cell for topology -->
          <template #cell-topology="{ row }">
            <GridLink :to="{ name: 'topology-detail', params: { id: row.topologyId } }">
              {{ getTopologyName(row.topologyId) }}
            </GridLink>
          </template>

          <!-- Custom cell for code -->
          <template #cell-code="{ value }">
            <StatusBadge :variant="value >= 400 && value < 500 ? 'yellow' : 'red'">
              {{ value }}
            </StatusBadge>
          </template>

          <!-- Custom cell for message -->
          <template #cell-message="{ value }">
            <span class="break-words text-xs">
              {{ value }}
            </span>
          </template>
        </DataGrid>
      </div>
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-else-if="loading" class="py-12" text="Loading connector details..." />
  </Drawer>

  <ConnectorMetricDetailModal
    v-model="metricDetailOpen"
    :record="selectedRecord"
  />
</template>

