<script setup lang="ts">
import { ref, computed, onMounted, onActivated, onDeactivated, nextTick, watch } from 'vue'
import { useApexChart, getChartColors, getBaseChartOptions } from '@/composables/useApexChart'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useTabDataFreshness } from '@/composables/useTabDataFreshness'
import { fetchLimiterData, fetchApplicationLimiterSettings } from '@/services/dashboardService'
import type { LimiterData, TableColumn, TimeFilter, AppLimiterSetting } from '@/types/dashboard'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'

const { formatChartLabel } = useDateFormat()

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
  globalTimeFilter: TimeFilter
  refreshKey?: number
}

const props = defineProps<Props>()

const { loadMappings, getNodeName, getTopologyName, getApplicationName } = useTopologyNodeMappings()
const { isActive, isStale, markFresh, invalidate } = useTabDataFreshness()

const limiterData = ref<LimiterData | null>(null)
const appSettings = ref<Map<string, AppLimiterSetting>>(new Map())
const chartEl = ref<HTMLElement | null>(null)
const chartMounted = ref(false)

// Compute % difference: how much max is above current (positive = decrease from peak)
const maxDiffPercent = computed(() => {
  if (!limiterData.value || limiterData.value.totalMessages === 0) return 0
  const { totalMessages, maxMessages } = limiterData.value
  if (maxMessages === totalMessages) return 0
  return Math.round(((maxMessages - totalMessages) / totalMessages) * 100)
})

const { initChart, setupResizeObserver, isDarkMode } = useApexChart({
  onDarkModeChange: () => {
    if (chartMounted.value && chartEl.value) {
      initChart(chartEl.value, getChartOptions())
      setupResizeObserver(chartEl.value)
    }
  },
})

const columns: TableColumn[] = [
  { key: 'application', label: 'Application', sortable: false },
  { key: 'connector', label: 'Connector', sortable: false },
  { key: 'topology', label: 'Topology', sortable: false },
  { key: 'limitSetting', label: 'Limit', sortable: false },
  { key: 'messages', label: 'Max (actual)', sortable: true },
]

// Load data function
const loadData = async () => {
  loading.value = true

  try {
    const response = await fetchLimiterData({
      page: currentPage.value,
      limit: itemsPerPage.value,
      sortBy: sortField.value,
      sortOrder: sortDirection.value,
      timeFilter: props.globalTimeFilter,
      appSettings: appSettings.value,
      buckets: 40,
    })

    limiterData.value = response
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems
    markFresh()

    await nextTick()
    if (chartMounted.value && chartEl.value) {
      initChart(chartEl.value, getChartOptions())
      setupResizeObserver(chartEl.value)
    }
  } catch (error) {
    console.error('Error loading limiter data:', error)
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
  defaultSort: { field: 'messages', direction: 'desc' },
  defaultPerPage: 10,
  onDataLoad: loadData,
})

watch(() => props.globalTimeFilter, () => {
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
    // Load mappings and application limiter settings in parallel
    const [, settings] = await Promise.all([
      loadMappings(),
      fetchApplicationLimiterSettings(),
    ])
    appSettings.value = settings

    // Load data
    await loadData()
    await nextTick()

    if (!chartEl.value || !limiterData.value) {
      return
    }

    initChart(chartEl.value, getChartOptions())
    setupResizeObserver(chartEl.value)
    chartMounted.value = true
  } catch (error) {
    console.error('LimiterTab mount error:', error)
  }
})

const getChartOptions = () => {
  const colors = getChartColors(isDarkMode.value)
  const categories = limiterData.value?.chartData.categories || []
  const granularity = getGranularityMinutes(props.globalTimeFilter)

  return {
    ...getBaseChartOptions(isDarkMode.value),
    series: [
      {
        name: 'Messages',
        data: limiterData.value?.chartData.series || [],
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
      categories,
      labels: {
        show: true,
        rotate: -45,
        rotateAlways: false,
        hideOverlappingLabels: true,
        trim: true,
        maxHeight: 60,
        style: {
          colors: colors.text,
          fontSize: '10px',
          fontFamily: 'Inter, sans-serif',
        },
        formatter: (value: string) => {
          if (!value) return ''
          const index = categories.indexOf(value)
          const step = Math.max(1, Math.floor(categories.length / 8))
          if (index !== -1 && index % step !== 0 && index !== categories.length - 1) {
            return ''
          }
          return formatChartLabel(value, granularity)
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
      show: false,
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
        const value = w.config.series[0].data[dataPointIndex]
        const rawCategory = categories[dataPointIndex] || ''
        const formattedDate = formatChartLabel(rawCategory, granularity)
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
            <div class="flex flex-col items-center">
              <span class="text-xs text-gray-500 dark:text-gray-400">actual</span>
              <div class="flex items-center gap-1">
                <svg
                  v-if="maxDiffPercent > 0"
                  class="h-6 w-6 text-green-600 dark:text-green-400"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4"/>
                </svg>
                <svg
                  v-else-if="maxDiffPercent < 0"
                  class="h-6 w-6 text-red-600 dark:text-red-400"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m0-14 4 4m-4-4-4 4"/>
                </svg>
                <span
                  class="text-2xl font-bold"
                  :class="maxDiffPercent > 0
                    ? 'text-green-600 dark:text-green-400'
                    : maxDiffPercent < 0
                      ? 'text-red-600 dark:text-red-400'
                      : 'text-gray-900 dark:text-white'"
                >
                  {{ limiterData.totalMessages }}
                  <span v-if="maxDiffPercent !== 0" class="text-sm font-medium">{{ Math.abs(maxDiffPercent) }}%</span>
                </span>
              </div>
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
          <span class="font-medium text-gray-900 dark:text-white">{{ row.applicationId && row.applicationId !== '-' ? getApplicationName(row.applicationId) : '-' }}</span>
        </template>
        <template #cell-connector="{ row }">
          <span class="text-gray-900 dark:text-white">{{ getNodeName(row.nodeId) }}</span>
        </template>
        <template #cell-topology="{ row }">
          <span class="text-gray-900 dark:text-white">{{ getTopologyName(row.topologyId) }}</span>
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
        <template #cell-messages="{ row }">
          {{ row.maxMessages }}
          <span
            :class="row.messages < row.maxMessages
              ? 'text-green-600 dark:text-green-400'
              : row.messages > row.maxMessages
                ? 'text-red-600 dark:text-red-400'
                : 'text-gray-500 dark:text-gray-400'"
          >
            <svg v-if="row.messages < row.maxMessages" class="inline h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4"/></svg>
            <svg v-else-if="row.messages > row.maxMessages" class="inline h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m0-14 4 4m-4-4-4 4"/></svg>
            ({{ row.messages }})
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
