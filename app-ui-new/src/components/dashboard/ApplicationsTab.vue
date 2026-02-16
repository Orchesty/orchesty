<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import ConnectorsHeatmapChart from './ConnectorsHeatmapChart.vue'
import type { ConnectorHeatmapData, ProcessFilter, TimeFilter } from '@/types/dashboard'
import { fetchConnectorHeatmapData } from '@/services/dashboardService'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApi } from '@/utils/timeRangeConverter'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

interface Props {
  timeFilter?: TimeFilter
  heatmapFilter?: ProcessFilter
}

const props = withDefaults(defineProps<Props>(), {
  timeFilter: '7d',
  heatmapFilter: 'all',
})

const emit = defineEmits<{
  heatmapFilterChange: [filter: ProcessFilter]
}>()

const { loadMappings, getNodeName, getApplicationName } = useTopologyNodeMappings()

const loading = ref(true)
const error = ref<string | null>(null)
const chartData = ref<ConnectorHeatmapData | null>(null)
// Map of connectorName -> applicationName (for y-axis labels)
const connectorAppMap = ref<Record<string, string>>({})

const loadData = async () => {
  loading.value = true
  error.value = null

  try {
    const range = convertTimeFilterToDateTimeRange(props.timeFilter)
    const dateFrom = formatDateTimeForApi(range.from) || ''
    const dateTo = formatDateTimeForApi(range.to) || ''

    const data = await fetchConnectorHeatmapData(props.heatmapFilter, dateFrom, dateTo)

    // Map nodeIds to connector names, then merge series with the same name.
    // Multiple nodeIds can map to the same connector name (same connector
    // deployed in different topologies). We sum success/failed per time slot.
    // Also build connectorName -> applicationName map for y-axis labels.
    const appMap: Record<string, string> = {}
    const namedSeries = data.series.map(s => {
      const connectorName = getNodeName(s.name)
      const appId = data.nodeAppMap.get(s.name) || ''
      if (appId && !appMap[connectorName]) {
        appMap[connectorName] = getApplicationName(appId)
      }
      return { ...s, name: connectorName }
    })

    const mergedMap = new Map<string, typeof namedSeries[0]>()
    for (const series of namedSeries) {
      const existing = mergedMap.get(series.name)
      if (!existing) {
        // First occurrence -- deep-copy data points so we can mutate safely
        mergedMap.set(series.name, {
          ...series,
          data: series.data.map(d => ({
            ...d,
            meta: { ...d.meta }
          }))
        })
      } else {
        // Merge: sum success/failed per time slot and recalculate y
        for (let i = 0; i < existing.data.length; i++) {
          const target = existing.data[i]
          const source = series.data[i]
          if (!target || !source) continue

          target.meta.success += source.meta.success
          target.meta.failed += source.meta.failed
          // Recalculate isFailed and y using the same offset logic
          target.meta.isFailed = target.meta.failed > 0
          const FAILED_OFFSET = 1000
          if (target.meta.success === 0 && target.meta.failed === 0) {
            target.y = 0
          } else if (target.meta.isFailed) {
            target.y = target.meta.failed + FAILED_OFFSET
          } else {
            target.y = target.meta.success
          }
        }
      }
    }

    connectorAppMap.value = appMap
    chartData.value = {
      ...data,
      series: Array.from(mergedMap.values())
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load data'
    console.error('Error loading applications heatmap data:', err)
  } finally {
    loading.value = false
  }
}

const handleFilterChange = (filter: ProcessFilter) => {
  emit('heatmapFilterChange', filter)
}

watch(() => props.timeFilter, () => {
  loadData()
})

watch(() => props.heatmapFilter, () => {
  loadData()
})

onMounted(async () => {
  await loadMappings()
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

  <div v-else-if="!loading && !error && chartData" class="space-y-6">
    <ConnectorsHeatmapChart
      chart-id="applications"
      :total-requests="chartData.totalRequests"
      :total-failed="chartData.totalFailed"
      time-range=""
      :filter="props.heatmapFilter"
      :series="chartData.series"
      :x-categories="chartData.xCategories"
      :y-label-prefix="connectorAppMap"
      @filter-change="handleFilterChange"
    />
  </div>
</template>
