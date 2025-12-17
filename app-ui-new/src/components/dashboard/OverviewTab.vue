<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import ProcessesChart from './ProcessesChart.vue'
import LimiterCard from './LimiterCard.vue'
import TrashCard from './TrashCard.vue'
import type { ProcessesChartData, LimiterData, TrashData, ProcessFilter, TimeFilter } from '@/types/dashboard'
import { fetchProcessesData, fetchLimiterData, fetchTrashData } from '@/services/dashboardService'

interface Props {
  timeFilter?: TimeFilter
}

const props = withDefaults(defineProps<Props>(), {
  timeFilter: '7d',
})

const loading = ref(true)
const error = ref<string | null>(null)
const processFilter = ref<ProcessFilter>('all')

const processesData = ref<ProcessesChartData | null>(null)
const limiterData = ref<LimiterData | null>(null)
const trashData = ref<TrashData | null>(null)

const loadData = async () => {
  loading.value = true
  error.value = null

  try {
    const [processes, limiter, trash] = await Promise.all([
      fetchProcessesData(processFilter.value, props.timeFilter),
      fetchLimiterData(props.timeFilter),
      fetchTrashData(props.timeFilter),
    ])

    processesData.value = processes
    limiterData.value = limiter
    trashData.value = trash
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load data'
    console.error('Error loading overview data:', err)
  } finally {
    loading.value = false
  }
}

const handleProcessFilterChange = async (filter: ProcessFilter) => {
  processFilter.value = filter
  try {
    processesData.value = await fetchProcessesData(filter, props.timeFilter)
  } catch (err) {
    console.error('Error reloading processes data:', err)
  }
}

watch(() => props.timeFilter, () => {
  loadData()
})

onMounted(() => {
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

  <div v-else-if="processesData && limiterData && trashData" class="space-y-6">
    <!-- Processes Heatmap -->
    <ProcessesChart
      :total-processes="processesData.totalProcesses"
      :total-failed="processesData.totalFailed"
      :time-range="processesData.timeRange"
      :filter="processFilter"
      :series="processesData.series"
      :x-categories="processesData.xCategories"
      :y-categories="processesData.yCategories"
      @filter-change="handleProcessFilterChange"
    />
    
    <!-- Limiter and Trash Cards -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <LimiterCard :data="limiterData" />
      <TrashCard :data="trashData" />
    </div>
  </div>
</template>

