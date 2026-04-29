<script setup lang="ts">
import { ref, computed, onMounted, onActivated, onDeactivated, nextTick, watch } from 'vue'
// Notes on the headline numbers:
//   - "Max" = peak per-minute summed messages across all nodes within the
//     selected time filter (metrics-derived, MetricLimitTotalAggregationFilter).
//   - "Live" = real-time count of messages currently held in the limiter
//     queue (limiter Mongo snapshot via /api/resources/limiter/snapshot).
//   - The grid mirrors "Max" per-node using the same aggregation algorithm
//     in MetricLimitAggregationFilter, with the same time filter applied.
//   - The previous "Actual" headline (last per-minute sum within 90s) was
//     removed because Live is more accurate and Actual confused users with
//     its 90s validity quirk and densify-related zeroing.
import { useApexChart, getChartColors, getBaseChartOptions } from '@/composables/useApexChart'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useTabDataFreshness } from '@/composables/useTabDataFreshness'
import { fetchLimiterData, fetchApplicationLimiterSettings, fetchLimiterApplications } from '@/services/dashboardService'
import type { LimiterApplicationRow } from '@/services/dashboardService'
import { fetchLimiterSnapshot } from '@/services/resourcesService'
import type { LimiterSnapshotItem } from '@/services/resourcesService'
import type { LimiterData, TableColumn, TimeFilter, AppLimiterSetting } from '@/types/dashboard'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import GridLink from '@/components/ui/datagrid/GridLink.vue'
import LimiterMessagesCell from './LimiterMessagesCell.vue'

const { formatChartLabel, formatDurationSeconds } = useDateFormat()

/**
 * Get granularity in minutes matching backend's getDateTruncBinSize logic
 */
function getGranularityMinutes(timeFilter: TimeFilter): number {
  switch (timeFilter) {
    case '1h': return 5
    case '24h': return 120
    case '7d': return 720
    default: return 1440 // 30d
  }
}

interface Props {
  timeFilter: TimeFilter
  refreshKey?: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'open-app-processes': [data: { applicationId: string; topologyIds: string[] }]
}>()

const { getNodeName, getTopologyName, getApplicationName, getApplicationNameByNodeId, mappings } = useTopologyNodeMappings()
const { isActive, isStale, markFresh, invalidate } = useTabDataFreshness()

const limiterData = ref<LimiterData | null>(null)
const appSettings = ref<Map<string, AppLimiterSetting>>(new Map())
// Per-application "Max" computed by the backend with the same per-(group,
// minute) → max-per-minute algorithm as the headline. Populated from
// /api/metrics/limits/applications. Used directly in `summaryData` instead
// of summing per-node maxes (which over-counts).
const applicationMax = ref<Map<string, number>>(new Map())
// Per-application live hold derived from the limiter snapshot
// (sum of `messages` for snapshot items grouped by `applicationId`).
const applicationLive = ref<Map<string, number>>(new Map())
const snapshotLoaded = ref(false)
const chartEl = ref<HTMLElement | null>(null)
const chartMounted = ref(false)

const { initChart, isDarkMode } = useApexChart({
  onDarkModeChange: () => {
    if (chartMounted.value && chartEl.value) {
      initChart(chartEl.value, getChartOptions())
    }
  },
})

const summaryColumns: TableColumn[] = [
  { key: 'application', label: 'Application', sortable: false },
  { key: 'limitSetting', label: 'Limit', sortable: false },
  { key: 'maximumCount', label: 'Max', sortable: false },
  { key: 'liveMessages', label: 'Live', sortable: false },
  { key: 'remainingTime', label: 'Remaining time', sortable: false },
  { key: 'actions', label: '', className: 'text-right w-16' },
]

const getTopologyIdsForApp = (applicationId: string): string[] => {
  if (!mappings.value?.applicationTree) return []
  const appKey = applicationId === '-' ? '' : applicationId
  const nodeIds = mappings.value.applicationTree[appKey]
    || mappings.value.applicationTree[applicationId]
    || []
  if (nodeIds.length === 0) return []

  const nodeIdSet = new Set(nodeIds)
  const topologyIds: string[] = []
  for (const [topoId, topoNodeIds] of Object.entries(mappings.value.topologyTree)) {
    if (topoNodeIds.some(nId => nodeIdSet.has(nId))) {
      topologyIds.push(topoId)
    }
  }
  return topologyIds
}

// Per-application summary. `maxMessages` is now read from the dedicated
// /api/metrics/limits/applications endpoint, which runs the same
// per-(group, minute) → max-per-minute algorithm as the headline (just
// grouped by `applicationId` instead of `nodeName`). This guarantees
//   app.max ≤ headline.max
// — no more "summary larger than headline" surprises that came from naively
// summing per-node maxes (which overcounted because nodes peak in
// different minutes).
//
// `liveMessages` is summed from the limiter snapshot grouped by
// `applicationId`, which IS a meaningful sum: `messages held *right now* by
// every node belonging to the app`.
const summaryData = computed(() => {
  // Union of apps that have *actual data* in the queried window — either a
  // historical max from metrics or a live entry from the snapshot. Apps
  // that only have a limit configured but never produced a record are
  // intentionally hidden, otherwise the summary fills up with empty rows
  // for every installed app, which the user found confusing.
  const appKeys = new Set<string>()
  applicationMax.value.forEach((_, key) => appKeys.add(key))
  applicationLive.value.forEach((_, key) => appKeys.add(key))

  return [...appKeys]
    .map(applicationId => {
      const maxMessages = applicationMax.value.get(applicationId) ?? 0
      const liveMessages = applicationLive.value.get(applicationId) ?? 0
      const setting = appSettings.value.get(applicationId)
      const limitSetting = setting && setting.useLimit && setting.value && setting.time
        ? `${setting.value} / ${setting.time}s`
        : 'off'

      let remainingTime = '-'
      if (liveMessages > 0 && setting?.useLimit && setting.value && setting.time) {
        const rate = setting.value / setting.time
        remainingTime = formatDurationSeconds(liveMessages / rate)
      }

      return { applicationId, maxMessages, liveMessages, limitSetting, remainingTime }
    })
    .sort((a, b) => (b.liveMessages - a.liveMessages) || (b.maxMessages - a.maxMessages))
})

const columns: TableColumn[] = [
  { key: 'application', label: 'Application', sortable: false },
  { key: 'connector', label: 'Connector', sortable: false },
  { key: 'topology', label: 'Topology', sortable: false },
  { key: 'limitSetting', label: 'Limit', sortable: false },
  { key: 'maximumCount', label: 'Max', sortable: true },
  { key: 'liveMessages', label: 'Live', sortable: false },
]

// Load data function
const loadData = async () => {
  loading.value = true

  try {
    const [response, applications, snapshot] = await Promise.all([
      fetchLimiterData({
        page: currentPage.value,
        limit: itemsPerPage.value,
        sortBy: sortField.value,
        sortOrder: sortDirection.value,
        timeFilter: props.timeFilter,
        appSettings: appSettings.value,
        buckets: 40,
      }),
      fetchLimiterApplications(props.timeFilter).catch((err) => {
        console.error('Limiter applications fetch failed:', err)
        return [] as LimiterApplicationRow[]
      }),
      fetchLimiterSnapshot().catch((err) => {
        console.error('Limiter snapshot failed:', err)
        return null
      }),
    ])

    applicationMax.value = new Map(
      applications.map(row => [row.applicationId || '-', row.maxMessages]),
    )

    if (snapshot) {
      applySnapshot(response, snapshot.totalMessages, snapshot.items)
      snapshotLoaded.value = true
    } else {
      applicationLive.value = new Map()
      snapshotLoaded.value = false
    }

    limiterData.value = response
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems
    markFresh()

    await nextTick()
    if (chartMounted.value && chartEl.value) {
      initChart(chartEl.value, getChartOptions())
    }
  } catch (error) {
    console.error('Error loading limiter data:', error)
  } finally {
    loading.value = false
  }
}

function applySnapshot(data: LimiterData, totalMessages: number, items: LimiterSnapshotItem[]): void {
  // Live values from the limiter service.
  // - liveTotalMessages: cross-app sum (matches the headline "live" pill).
  // - per-row liveMessages: filled by nodeId for the per-node detail grid.
  // - applicationLive map: filled by applicationId for the per-app summary.
  data.liveTotalMessages = totalMessages

  const snapshotByNode = new Map<string, number>()
  const liveByApp = new Map<string, number>()
  for (const item of items) {
    snapshotByNode.set(item.nodeId, item.messages)
    const appKey = item.applicationId || '-'
    liveByApp.set(appKey, (liveByApp.get(appKey) ?? 0) + item.messages)
  }

  applicationLive.value = liveByApp

  for (const row of data.tableData) {
    row.liveMessages = snapshotByNode.get(row.nodeId) ?? 0
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
  defaultSort: { field: 'maximumCount', direction: 'desc' },
  defaultPerPage: 10,
  onDataLoad: loadData,
})

watch(() => props.timeFilter, () => {
  invalidate()
  if (isActive.value) loadData()
})

watch(() => props.refreshKey, () => {
  invalidate()
  loadData()
})

onActivated(async () => {
  isActive.value = true
  if (isStale()) {
    await loadData()
  }
  await nextTick()
  if (chartMounted.value && chartEl.value && limiterData.value) {
    initChart(chartEl.value, getChartOptions())
  }
})

onDeactivated(() => {
  isActive.value = false
})

// Initialize on mount
onMounted(async () => {
  try {
    const settings = await fetchApplicationLimiterSettings()
    appSettings.value = settings
  } catch {
    // application:read not available for this role — limiter data still works
  }

  try {
    await loadData()
    await nextTick()

    if (!chartEl.value || !limiterData.value) {
      return
    }

    initChart(chartEl.value, getChartOptions())
    chartMounted.value = true
  } catch (error) {
    console.error('LimiterTab mount error:', error)
  }
})

const getChartOptions = () => {
  const colors = getChartColors(isDarkMode.value)
  const categories = limiterData.value?.chartData.categories || []
  const seriesData = limiterData.value?.chartData.series || []
  const granularity = getGranularityMinutes(props.timeFilter)

  const pairedData = categories.map((cat, i) => ({
    x: new Date(cat).getTime(),
    y: seriesData[i] ?? 0,
  }))

  return {
    ...getBaseChartOptions(isDarkMode.value),
    series: [
      {
        name: 'Messages',
        data: pairedData,
      },
    ],
    chart: {
      type: 'area',
      height: 320,
      toolbar: {
        show: false,
      },
      background: 'transparent',
      dropShadow: {
        enabled: false,
      },
    },
    fill: {
      type: 'gradient',
      gradient: {
        opacityFrom: 0.55,
        opacityTo: 0,
        shade: colors.primary,
        gradientToColors: [colors.primary],
      },
    },
    stroke: {
      width: 4,
      curve: 'smooth',
    },
    colors: [colors.primary],
    dataLabels: {
      enabled: false,
    },
    xaxis: {
      type: 'datetime',
      labels: {
        show: true,
        rotate: -45,
        rotateAlways: false,
        hideOverlappingLabels: true,
        trim: true,
        maxHeight: 60,
        datetimeUTC: false,
        style: {
          colors: colors.text,
          fontSize: '10px',
          fontFamily: 'Inter, sans-serif',
        },
        formatter: (_value: string, timestamp?: number) => {
          if (!timestamp) return ''
          return formatChartLabel(new Date(timestamp).toISOString(), granularity)
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: true,
      labels: {
        style: {
          colors: colors.text,
        },
      },
    },
    tooltip: {
      theme: isDarkMode.value ? 'dark' : 'light',
      shared: false,
      followCursor: true,
      style: {
        fontSize: '13px',
        fontFamily: 'Inter, sans-serif',
      },
      custom: ({ dataPointIndex, w }: { dataPointIndex: number; w: any }) => {
        const point = w.config.series[0].data[dataPointIndex]
        const value = point?.y ?? point
        const timestamp = point?.x ? new Date(point.x).toISOString() : ''
        const formattedDate = formatChartLabel(timestamp, granularity)
        const dark = isDarkMode.value
        const bg = dark ? '#1f2937' : '#ffffff'
        const textPrimary = dark ? '#f9fafb' : '#111827'
        const textSecondary = dark ? '#9ca3af' : '#6b7280'

        return `
          <div style="background:${bg};border-radius:8px;padding:12px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.3);border:none;">
            <div style="font-size:12px;color:${textSecondary};margin-bottom:4px;">
              ${formattedDate}
            </div>
            <div style="font-size:14px;font-weight:500;color:${textPrimary};">
              Messages: <span style="font-weight:700;">${value}</span>
            </div>
          </div>
        `
      },
    },
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Section: Chart card -->
    <Card>
      <div v-if="limiterData">
        <!-- Header with total count -->
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Limiter</h3>
          <div class="flex items-center gap-6">
            <div class="flex flex-col items-center">
              <span class="text-xs text-gray-500 dark:text-gray-400">max</span>
              <span class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ limiterData.maxMessages }}
              </span>
            </div>
            <div v-if="snapshotLoaded" class="flex flex-col items-center">
              <span class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                <span
                  class="inline-block h-2 w-2 rounded-full"
                  :class="(limiterData.liveTotalMessages ?? 0) > 0
                    ? 'bg-red-500 animate-pulse'
                    : 'bg-green-500'"
                  aria-hidden="true"
                ></span>
                live
              </span>
              <span
                class="text-2xl font-bold"
                :class="(limiterData.liveTotalMessages ?? 0) > 0
                  ? 'text-red-600 dark:text-red-400'
                  : 'text-gray-900 dark:text-white'"
              >
                {{ limiterData.liveTotalMessages ?? 0 }}
              </span>
            </div>
          </div>
        </div>

        <!-- Chart -->
        <div class="relative h-80 overflow-visible">
          <div ref="chartEl" class="h-full"></div>
        </div>
      </div>
      <div v-else class="flex h-80 items-center justify-center">
        <div class="text-gray-500 dark:text-gray-400">Loading...</div>
      </div>
    </Card>

    <!-- Section: Summary by application -->
    <Card>
      <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Summary by application</h3>

      <DataGrid
        :columns="summaryColumns"
        :data="summaryData"
        :loading="loading"
        :current-page="1"
        :items-per-page="100"
        hide-pagination
      >
        <template #cell-application="{ row }">
          <span class="font-medium text-gray-900 dark:text-white">{{ row.applicationId && row.applicationId !== '-' ? getApplicationName(row.applicationId) : '-' }}</span>
        </template>
        <template #cell-limitSetting="{ value }">
          <span
            :class="value === 'off'
              ? 'text-gray-400 dark:text-gray-500'
              : 'text-gray-900 dark:text-white font-medium'"
          >
            {{ value }}
          </span>
        </template>
        <template #cell-maximumCount="{ row }">
          <LimiterMessagesCell :max-messages="row.maxMessages" />
        </template>
        <template #cell-liveMessages="{ row }">
          <span
            class="whitespace-nowrap font-medium"
            :class="(row.liveMessages ?? 0) > 0
              ? 'text-red-600 dark:text-red-400'
              : 'text-gray-400 dark:text-gray-500'"
          >
            {{ row.liveMessages ?? 0 }}
          </span>
        </template>
        <template #cell-remainingTime="{ value }">
          <span class="whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ value }}</span>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end">
            <button
              type="button"
              title="Running processes"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              @click="emit('open-app-processes', { applicationId: row.applicationId, topologyIds: getTopologyIdsForApp(row.applicationId) })"
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
              <span class="sr-only">Running processes</span>
            </button>
          </div>
        </template>
      </DataGrid>
    </Card>

    <!-- Section: Grid card -->
    <Card>
      <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Limiter details</h3>

      <DataGrid
        :columns="columns"
        :data="limiterData?.tableData || []"
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
        <template #cell-application="{ row }">
          <span class="font-medium text-gray-900 dark:text-white">{{ row.nodeId && row.applicationId !== '-' ? getApplicationNameByNodeId(row.nodeId) : '-' }}</span>
        </template>
        <template #cell-connector="{ row }">
          <span class="text-gray-900 dark:text-white">{{ getNodeName(row.nodeId) }}</span>
        </template>
        <template #cell-topology="{ row }">
          <GridLink :to="{ name: 'topology-detail', params: { id: row.topologyId } }">
            {{ getTopologyName(row.topologyId) }}
          </GridLink>
        </template>
        <template #cell-limitSetting="{ value }">
          <span
            :class="value === 'off'
              ? 'text-gray-400 dark:text-gray-500'
              : 'text-gray-900 dark:text-white font-medium'"
          >
            {{ value }}
          </span>
        </template>
        <template #cell-maximumCount="{ row }">
          <LimiterMessagesCell :max-messages="row.maxMessages" />
        </template>
        <template #cell-liveMessages="{ row }">
          <span
            class="whitespace-nowrap font-medium"
            :class="(row.liveMessages ?? 0) > 0
              ? 'text-red-600 dark:text-red-400'
              : 'text-gray-400 dark:text-gray-500'"
          >
            {{ row.liveMessages ?? 0 }}
          </span>
        </template>
      </DataGrid>
    </Card>
  </div>
</template>

<style scoped>
/* Override ApexCharts tooltip wrapper so our custom content controls all styling */
:deep(.apexcharts-tooltip) {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
}
</style>
