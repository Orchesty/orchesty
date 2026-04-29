<script setup lang="ts">
import { ref, onMounted, onActivated, nextTick, watch } from 'vue'
import { useApexChart, getChartColors, getBaseChartOptions } from '@/composables/useApexChart'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'
import { fetchLimiterData } from '@/services/dashboardService'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import type { LimiterData, TableColumn, TimeFilter } from '@/types/dashboard'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import GridLink from '@/components/ui/datagrid/GridLink.vue'
import LimiterMessagesCell from './LimiterMessagesCell.vue'

const { formatChartLabel } = useDateFormat()
const { getNodeName, getTopologyName } = useTopologyNodeMappings()

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
}

const props = defineProps<Props>()

const emit = defineEmits<{
  viewAll: []
}>()

const limiterData = ref<LimiterData | null>(null)
const chartEl = ref<HTMLElement | null>(null)
const chartMounted = ref(false)

const { initChart, isDarkMode } = useApexChart({
  onDarkModeChange: () => {
    if (chartMounted.value && chartEl.value) {
      initChart(chartEl.value, getColumnChartOptions())
    }
  },
})

const columns: TableColumn[] = [
  { key: 'connector', label: 'Connector', sortable: false, className: 'w-[35%] truncate' },
  { key: 'topology', label: 'Topology', sortable: false, className: 'w-[35%] truncate' },
  // Sort key matches the backend column (`maximumCount`) so the grid sort
  // stays in sync with what the cell actually shows.
  { key: 'maximumCount', label: 'Max', sortable: true, className: 'w-[30%] whitespace-nowrap' },
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
      timeFilter: props.timeFilter,
      buckets: 40
    })

    limiterData.value = response
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems

    await nextTick()
    if (chartMounted.value && chartEl.value) {
      initChart(chartEl.value, getColumnChartOptions())
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
  defaultSort: { field: 'maximumCount', direction: 'desc' },
  defaultPerPage: 5,
  onDataLoad: loadData,
})

// Add watcher for timeFilter changes
watch(() => props.timeFilter, () => {
  loadData()
})

onMounted(async () => {
  try {
    await loadData()
    await nextTick()
    if (!chartEl.value || !limiterData.value) {
      return
    }

    initChart(chartEl.value, getColumnChartOptions())
    chartMounted.value = true
  } catch (error) {
    console.error('LimiterCard mount error:', error)
  }
})

onActivated(() => {
  nextTick(() => {
    if (chartMounted.value && chartEl.value && limiterData.value) {
      initChart(chartEl.value, getColumnChartOptions())
    }
  })
})

const getColumnChartOptions = () => {
  const colors = getChartColors(isDarkMode.value)
  const categories = limiterData.value?.chartData.categories || []
  const seriesData = limiterData.value?.chartData.series || []
  const granularity = getGranularityMinutes(props.timeFilter)

  return {
    ...getBaseChartOptions(isDarkMode.value),
    series: [
      {
        name: 'Messages',
        data: seriesData,
      },
    ],
    chart: {
      type: 'area',
      height: 256,
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
          // Show only every Nth label to avoid clutter
          const index = categories.indexOf(value)
          const step = Math.max(1, Math.floor(categories.length / 6))
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
  <Card>
    <div v-if="limiterData">
      <!-- Header with total count -->
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Limiter</h3>
        <div class="flex flex-col items-center">
          <span class="text-xs text-gray-500 dark:text-gray-400">max</span>
          <span class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ limiterData.maxMessages }}
          </span>
        </div>
      </div>

      <!-- Chart -->
      <div class="relative mb-4 h-64 overflow-visible">
        <div ref="chartEl" class="h-full"></div>
      </div>

      <!-- Table -->
      <DataGrid
        :columns="columns"
        :data="limiterData.tableData"
        table-fixed
        hide-pagination
        :sort-field="sortField"
        :sort-direction="sortDirection"
        :loading="loading"
        @sort="handleSort"
      >
        <template #cell-connector="{ row }">
          <span class="font-medium text-gray-900 dark:text-white">{{ getNodeName(row.nodeId) }}</span>
        </template>
        <template #cell-topology="{ row }">
          <GridLink :to="{ name: 'topology-detail', params: { id: row.topologyId } }">
            {{ getTopologyName(row.topologyId) }}
          </GridLink>
        </template>
        <template #cell-maximumCount="{ row }">
          <LimiterMessagesCell :max-messages="row.maxMessages" />
        </template>
      </DataGrid>

      <div class="mt-4">
        <button
          type="button"
          class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-500"
          @click="emit('viewAll')"
        >
          View all →
        </button>
      </div>
    </div>
    <div v-else class="flex items-center justify-center h-64">
      <div class="text-gray-500 dark:text-gray-400">Loading...</div>
    </div>
  </Card>
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
