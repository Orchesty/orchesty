<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import HeatmapChart from './HeatmapChart.vue'
import type { HeatmapSeries, ProcessFilter, TimeFilter } from '@/types/dashboard'
import { fetchConnectorHeatmapData } from '@/services/dashboardService'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApi } from '@/utils/timeRangeConverter'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

interface ApplicationHeatmapGroup {
  applicationName: string
  series: HeatmapSeries[]
  xCategories: string[]
  totalRequests: number
  totalFailed: number
}

interface Props {
  timeFilter?: TimeFilter
  heatmapFilter?: ProcessFilter
}

const props = withDefaults(defineProps<Props>(), {
  timeFilter: '7d',
  heatmapFilter: 'all',
})

const { loadMappings, getNodeName, getApplicationName } = useTopologyNodeMappings()

const loading = ref(true)
const error = ref<string | null>(null)
const applicationGroups = ref<ApplicationHeatmapGroup[]>([])

const loadData = async () => {
  loading.value = true
  error.value = null

  try {
    const range = convertTimeFilterToDateTimeRange(props.timeFilter)
    const dateFrom = formatDateTimeForApi(range.from) || ''
    const dateTo = formatDateTimeForApi(range.to) || ''

    const data = await fetchConnectorHeatmapData(props.heatmapFilter, dateFrom, dateTo)

    // Map nodeIds to connector names and resolve application names.
    // Track which application each connector belongs to.
    const connectorAppName: Record<string, string> = {}
    const namedSeries = data.series.map(s => {
      const connectorName = getNodeName(s.name)
      const appId = data.nodeAppMap.get(s.name) || ''
      if (appId && !connectorAppName[connectorName]) {
        connectorAppName[connectorName] = getApplicationName(appId)
      }
      const appName = connectorAppName[connectorName] || 'Unknown'
      // Remove application name prefix from connector name, ignoring case and treating hyphens as spaces
      // e.g. app "json-placeholder", connector "Json Placeholder Get Post" -> "Get Post"
      const normalize = (str: string) => str.toLowerCase().replace(/-/g, ' ')
      const normConnector = normalize(connectorName)
      const normApp = normalize(appName)
      const displayName = normConnector.startsWith(normApp)
        ? connectorName.slice(appName.length).replace(/^[\s-]+/, '')
        : connectorName
      return { ...s, name: displayName || connectorName, _appName: appName }
    })

    // Merge series with the same connector name (same connector in different topologies)
    const mergedMap = new Map<string, (typeof namedSeries)[0]>()
    for (const series of namedSeries) {
      const existing = mergedMap.get(series.name)
      if (!existing) {
        mergedMap.set(series.name, {
          ...series,
          data: series.data.map(d => ({ ...d, meta: { ...d.meta } })),
        })
      } else {
        for (let i = 0; i < existing.data.length; i++) {
          const target = existing.data[i]
          const source = series.data[i]
          if (!target || !source) continue

          target.meta.success += source.meta.success
          target.meta.failed += source.meta.failed
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

    // Group merged series by application name
    const groupMap = new Map<string, HeatmapSeries[]>()
    for (const series of mergedMap.values()) {
      const appName = series._appName
      if (!groupMap.has(appName)) {
        groupMap.set(appName, [])
      }
      groupMap.get(appName)!.push({ name: series.name, data: series.data })
    }

    // Build per-application groups with metrics
    const groups: ApplicationHeatmapGroup[] = []
    for (const [appName, series] of groupMap) {
      let totalRequests = 0
      let totalFailed = 0
      for (const s of series) {
        for (const d of s.data) {
          if (d.meta) {
            totalRequests += d.meta.success + d.meta.failed
            totalFailed += d.meta.failed
          }
        }
      }
      groups.push({
        applicationName: appName,
        series,
        xCategories: data.xCategories,
        totalRequests,
        totalFailed,
      })
    }

    // Sort groups alphabetically by application name
    groups.sort((a, b) => a.applicationName.localeCompare(b.applicationName))

    applicationGroups.value = groups
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load data'
    console.error('Error loading applications heatmap data:', err)
  } finally {
    loading.value = false
  }
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

  <div v-else-if="!loading && !error && applicationGroups.length > 0" class="space-y-6">
    <HeatmapChart
      v-for="group in applicationGroups"
      :key="group.applicationName"
      :chart-id="`app-${group.applicationName.toLowerCase().replace(/\s+/g, '-')}`"
      :title="group.applicationName"
      total-label="Requests"
      :total-count="group.totalRequests"
      :total-failed="group.totalFailed"
      :series="group.series"
      :x-categories="group.xCategories"
      :show-filter="false"
      empty-label="No requests"
    />
  </div>

  <div v-else-if="!loading && !error" class="flex items-center justify-center p-12">
    <p class="text-gray-500 dark:text-gray-400">No application data available</p>
  </div>
</template>
