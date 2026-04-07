<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import TimeFilter from '@/components/ui/TimeFilter.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import AuditErrorRecordsFailedMessagesTabs from '@/components/dashboard/AuditErrorRecordsFailedMessagesTabs.vue'
import type { Connector, ConnectorDetail } from '@/types/connectors'
import type { TimeFilter as TimeFilterType } from '@/types/dashboard'
import { fetchConnectorDetail, fetchConnectorChartData } from '@/services/connectorsService'
import { useApexChart } from '@/composables/useApexChart'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useDateFormat } from '@/composables/useDateFormat'
import StatusBadge from '@/components/ui/StatusBadge.vue'

interface Props {
  modelValue: boolean
  connector: Connector | null
  timeFilter: TimeFilterType
  showBackButton?: boolean
  backLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  showBackButton: false,
  backLabel: 'Back',
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'back': []
  'hidden': []
}>()

// Use topology/node mappings composable
const { getApplicationNameByNodeId, getNodeName, getNodeIdsByName } = useTopologyNodeMappings()
const { formatDurationMs } = useDateFormat()

// Local time filter (independent from global)
const localTimeFilter = ref<TimeFilterType>(props.timeFilter)

// Data state
const connectorDetail = ref<ConnectorDetail | null>(null)
const loading = ref(false)

const resolvedNodeIds = computed(() => {
  if (!props.connector) return [] as string[]
  return props.connector.nodeIds.length > 0
    ? props.connector.nodeIds
    : getNodeIdsByName(props.connector.name)
})

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

  const resolvedIds = resolvedNodeIds.value

  try {
    const detail = await fetchConnectorDetail(resolvedIds, localTimeFilter.value)
    connectorDetail.value = detail

    const chart = await fetchConnectorChartData(resolvedIds, localTimeFilter.value, 20)
    chartData.value = chart
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
      loadData()
    }
  },
)

watch(localTimeFilter, () => {
  loadData()
})
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="connector-detail-drawer"
    label="Connector Details"
    @update:model-value="emit('update:modelValue', $event)"
    @hidden="emit('hidden')"
  >
    <!-- Header Actions Slot -->
    <template #header-actions>
      <button
        v-if="showBackButton"
        type="button"
        class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
        @click="emit('back')"
      >
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        {{ backLabel }}
      </button>
      <div v-if="connector" class="flex items-center justify-between">
        <div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ connector.name || (connector.nodeIds.length > 0 ? getNodeName(connector.nodeIds[0]) : '') }}</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ connector.application || (connector.nodeIds.length > 0 ? getApplicationNameByNodeId(connector.nodeIds[0]) : '') }}</p>
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

      <AuditErrorRecordsFailedMessagesTabs
        v-if="connector"
        filter-mode="connector"
        :node-ids="resolvedNodeIds"
        :time-filter="localTimeFilter"
      />
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-else-if="loading" class="py-12" text="Loading connector details..." />
  </Drawer>
</template>

