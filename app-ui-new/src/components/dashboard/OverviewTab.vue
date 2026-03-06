<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import ProcessesChart from './ProcessesChart.vue'
import LimiterCard from './LimiterCard.vue'
import TrashCard from './TrashCard.vue'
import type { ProcessesChartData, ProcessFilter, TimeFilter, HeatmapClickData } from '@/types/dashboard'
import { fetchProcessesTotalCounts, fetchProcessesGraphData } from '@/services/dashboardService'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApi } from '@/utils/timeRangeConverter'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

interface Props {
  timeFilter?: TimeFilter
  heatmapFilter?: ProcessFilter
  refreshKey?: number
}

const props = withDefaults(defineProps<Props>(), {
  timeFilter: '7d',
  heatmapFilter: 'all',
})

const emit = defineEmits<{
  heatmapClick: [data: HeatmapClickData]
  heatmapFilterChange: [filter: ProcessFilter]
  limiterViewAll: []
}>()

// Use topology/node mappings composable
const { loadMappings, topologyNameMap } = useTopologyNodeMappings()

const loading = ref(true)
const error = ref<string | null>(null)

const processesData = ref<ProcessesChartData | null>(null)

const loadData = async () => {
  loading.value = true
  error.value = null

  try {
    // Convert time filter to date range
    const range = convertTimeFilterToDateTimeRange(props.timeFilter)
    const dateFrom = formatDateTimeForApi(range.from) || ''
    const dateTo = formatDateTimeForApi(range.to) || ''

    // Fetch total counts
    const totals = await fetchProcessesTotalCounts(dateFrom, dateTo)

    // Fetch graph data
    const chartData = await fetchProcessesGraphData(props.heatmapFilter, dateFrom, dateTo, 40)

    // Store raw chart data - topology IDs are resolved to names via yLabelMap in the chart
    processesData.value = {
      ...chartData,
      totalProcesses: totals.totalProcesses,
      failedProcesses: totals.failedProcesses,
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load data'
    console.error('Error loading overview data:', err)
  } finally {
    loading.value = false
  }
}

const handleHeatmapClick = (data: HeatmapClickData) => {
  emit('heatmapClick', data)
}

const handleProcessFilterChange = (filter: ProcessFilter) => {
  emit('heatmapFilterChange', filter)
  // Data reload will happen via watch on props.heatmapFilter
}

watch(() => props.timeFilter, () => {
  loadData()
})

watch(() => props.heatmapFilter, () => {
  loadData()
})

watch(() => props.refreshKey, () => {
  loadData()
})

onMounted(async () => {
  // Load mappings for topology names
  await loadMappings()

  // Load initial data
  loadData()
})
</script>

<template>
  <div v-if="loading" class="flex items-center justify-center p-12">
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
      <p class="mt-4 text-gray-500 dark:text-gray-400">Loading data...</p>
    </div>
  </div>

  <div v-else-if="error" class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
    <p class="text-red-800 dark:text-red-400">{{ error }}</p>
  </div>

  <div v-else-if="!loading && !error && processesData" class="space-y-6">
    <!-- Processes Heatmap -->
    <ProcessesChart
      chart-id="overview"
      :total-processes="processesData.totalProcesses || 0"
      :total-failed="processesData.failedProcesses || 0"
      :time-range="processesData.timeRange || ''"
      :filter="props.heatmapFilter"
      :series="processesData.series"
      :x-categories="processesData.xCategories || []"
      :y-label-map="topologyNameMap"
      @filter-change="handleProcessFilterChange"
      @heatmap-click="handleHeatmapClick"
    />

    <!-- Limiter and Trash Cards -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <LimiterCard :time-filter="props.timeFilter" @view-all="emit('limiterViewAll')" />
      <TrashCard />
    </div>
  </div>
</template>

