<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import TimeFilter from '@/components/ui/TimeFilter.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import type { Connector, ConnectorDetail, ConnectorErrorRecord } from '@/types/connectors'
import type { TimeFilter as TimeFilterType, TableColumn } from '@/types/dashboard'
import { fetchConnectorDetail, fetchConnectorErrorRecords, fetchConnectorChartData } from '@/services/connectorsService'
import { useApexChart } from '@/composables/useApexChart'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

interface Props {
  modelValue: boolean
  connector: Connector | null
  globalTimeFilter: TimeFilterType
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

// Use topology/node mappings composable
const { getTopologyName } = useTopologyNodeMappings()

// Local time filter (independent from global)
const localTimeFilter = ref<TimeFilterType>(props.globalTimeFilter)

// Data state
const connectorDetail = ref<ConnectorDetail | null>(null)
const errorRecords = ref<ConnectorErrorRecord[]>([])
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const itemsPerPage = ref(10)
const loading = ref(false)

// Chart data
const chartData = ref<{ categories: string[]; errors400: number[]; errors500: number[] } | null>(null)

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

// Initialize chart
const getChartOptions = () => {
  if (!chartData.value) return null

  return {
    series: [
      {
        name: '400 Errors',
        data: chartData.value.errors400,
        color: '#F59E0B', // yellow-500
      },
      {
        name: '500 Errors',
        data: chartData.value.errors500,
        color: '#EF4444', // red-500
      },
    ],
    chart: {
      type: 'area',
      height: 300,
      toolbar: {
        show: false,
      },
      zoom: {
        enabled: false,
      },
      background: 'transparent',
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      curve: 'smooth',
      width: 2,
    },
    fill: {
      type: 'gradient',
      gradient: {
        opacityFrom: 0.5,
        opacityTo: 0.1,
      },
    },
    xaxis: {
      categories: chartData.value.categories,
      labels: {
        style: {
          colors: isDarkMode.value ? '#9CA3AF' : '#6B7280',
        },
      },
    },
    yaxis: {
      labels: {
        formatter: (value: number) => Math.floor(value).toString(),
        style: {
          colors: isDarkMode.value ? '#9CA3AF' : '#6B7280',
        },
      },
    },
    legend: {
      position: 'top',
      horizontalAlign: 'right',
      labels: {
        colors: isDarkMode.value ? '#9CA3AF' : '#6B7280',
      },
    },
    grid: {
      borderColor: isDarkMode.value ? '#374151' : '#E5E7EB',
    },
    tooltip: {
      theme: isDarkMode.value ? 'dark' : 'light',
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
  if (status === 200) return 'green'
  if (status >= 400 && status < 500) return 'yellow'
  return 'red'
})

// Load data
const loadData = async () => {
  if (!props.connector) return

  loading.value = true

  try {
    // Fetch connector detail
    const detail = await fetchConnectorDetail(props.connector.id, localTimeFilter.value)
    connectorDetail.value = detail

    // Fetch chart data
    const chart = await fetchConnectorChartData(props.connector.id, localTimeFilter.value)
    chartData.value = chart

    // Fetch error records
    // Map sort field from table column key to API column name
    const apiSortField = sortField.value === 'timestamp' ? 'created' : sortField.value
    const records = await fetchConnectorErrorRecords(
      props.connector.id,
      localTimeFilter.value,
      currentPage.value,
      itemsPerPage.value,
      getTopologyName,
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
      localTimeFilter.value = props.globalTimeFilter
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

// Table columns for error records
const errorRecordsColumns: TableColumn[] = [
  { key: 'timestamp', label: 'Timestamp', sortable: true, className: 'w-48' },
  { key: 'topology', label: 'Topology' },
  { key: 'code', label: 'Code' },
  { key: 'message', label: 'Error Message' },
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
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ connector.name }}</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ connector.application }}</p>
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
      <div class="grid grid-cols-4 gap-3">
        <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-700">
          <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
            {{ connectorDetail.errors400 }}
          </div>
          <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">Total 400 Errors</div>
        </div>
        <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-700">
          <div class="text-2xl font-bold text-red-600 dark:text-red-400">
            {{ connectorDetail.errors500 }}
          </div>
          <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">Total 500 Errors</div>
        </div>
        <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-700">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ connectorDetail.totalRequests.toLocaleString() }}
          </div>
          <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">Total requests</div>
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
            Last request
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
          <template #cell-topology="{ value }">
            <span class="max-w-xs truncate whitespace-nowrap" :title="value">
              {{ value }}
            </span>
          </template>

          <!-- Custom cell for code -->
          <template #cell-code="{ value }">
            <span
              :class="[
                'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                value >= 400 && value < 500
                  ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-300'
                  : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-300',
              ]"
            >
              {{ value }}
            </span>
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
</template>

