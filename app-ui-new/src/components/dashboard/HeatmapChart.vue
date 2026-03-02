<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useApexChart, getChartColors, getBaseChartOptions } from '@/composables/useApexChart'
import type { ProcessFilter } from '@/types/dashboard'
import Card from '@/components/ui/Card.vue'

interface Props {
  chartId?: string
  title: string
  totalLabel: string
  totalCount: number
  totalFailed: number
  timeRange?: string
  emptyLabel?: string
  filter?: ProcessFilter
  series: any[]
  xCategories: string[]
  /** Optional map of seriesName (ID) -> display name. Used to resolve raw IDs to human-readable labels. */
  yLabelMap?: Record<string, string>
  /** Optional map of displayName -> prefix label (e.g. application name above connector name) */
  yLabelPrefix?: Record<string, string>
  /** Whether to show the All/Failed filter radio buttons (default: true) */
  showFilter?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  chartId: 'default',
  timeRange: '',
  emptyLabel: 'No data',
  filter: 'all',
  showFilter: true,
})

// Unique ids for radio buttons to avoid conflicts between multiple instances
const radioName = computed(() => `heatmap-filter-${props.chartId}`)
const radioAllId = computed(() => `heatmap-filter-all-${props.chartId}`)
const radioFailedId = computed(() => `heatmap-filter-failed-${props.chartId}`)

const emit = defineEmits<{
  filterChange: [filter: ProcessFilter]
  heatmapClick: [data: { name: string; nodeId?: string; nodeIds?: string[]; timeSlot: string; timeSlotEnd: string }]
}>()

const chartEl = ref<HTMLElement | null>(null)
const activeFilter = ref<ProcessFilter>(props.filter)

// Sync activeFilter when parent changes the prop
watch(
  () => props.filter,
  (newFilter) => {
    activeFilter.value = newFilter
  }
)

const chartMounted = ref(false)

// Dynamic chart height: fixed row height + padding for x-axis labels and margins
const ROW_HEIGHT = 38
const CHART_PADDING = 80
const MIN_HEIGHT = 150
const chartHeight = computed(() => Math.max(props.series.length * ROW_HEIGHT + CHART_PADDING, MIN_HEIGHT))

const { isDarkMode, initChart, setupResizeObserver } = useApexChart({
  onDarkModeChange: () => {
    if (chartMounted.value && chartEl.value && props.series.length > 0) {
      initChart(chartEl.value, getHeatmapOptions())
      setupResizeObserver(chartEl.value)
    }
  },
})

const getHeatmapOptions = () => {
  const colors = getChartColors(isDarkMode.value)
  const red = [224, 36, 36]
  const R = (a: number) => `rgba(${red[0]}, ${red[1]}, ${red[2]}, ${a})`
  const green = [34, 197, 94]
  const G = (a: number) => `rgba(${green[0]}, ${green[1]}, ${green[2]}, ${a})`

  // Calculate max values for dynamic ranges
  let maxSuccess = 0
  let maxFailed = 0
  props.series.forEach((s) => {
    s.data.forEach((d: any) => {
      if (d.meta) {
        if (d.meta.success > maxSuccess) maxSuccess = d.meta.success
        if (d.meta.failed > maxFailed) maxFailed = d.meta.failed
      }
    })
  })

  // Build success ranges (green palette)
  const successRanges = [{ from: 0, to: 0, color: colors.transparent, name: 'none' }]
  if (maxSuccess > 0) {
    const steps = 5
    const stepSize = Math.ceil(maxSuccess / steps)
    for (let i = 0; i < steps; i++) {
      const from = i * stepSize + 1
      const to = i < steps - 1 ? (i + 1) * stepSize : maxSuccess
      const opacity = 0.15 + (i + 1) * 0.15
      successRanges.push({
        from,
        to,
        color: G(opacity),
        name: `success-${i + 1}`,
      })
    }
  }

  // Build failed ranges (red palette)
  const failedRanges = []
  if (maxFailed > 0) {
    failedRanges.push({
      from: 1001,
      to: 1000 + maxFailed,
      color: R(1),
      name: 'failed',
    })
  }

  const allRanges = [...successRanges, ...failedRanges]
  const dark = isDarkMode.value
  const emptyLabel = props.emptyLabel

  return {
    ...getBaseChartOptions(isDarkMode.value),
    series: props.series,
    chart: {
      id: `heatmap-${props.chartId}`,
      width: '100%',
      height: chartHeight.value,
      type: 'heatmap',
      background: 'transparent',
      redrawOnWindowResize: true,
      redrawOnParentResize: true,
      parentHeightOffset: 0,
      toolbar: {
        show: false,
      },
      events: {
        dataPointSelection: (_event: any, _chartContext: any, config: any) => {
          const { seriesIndex, dataPointIndex } = config
          if (seriesIndex >= 0 && dataPointIndex >= 0 && props.series[seriesIndex]) {
            const name = props.series[seriesIndex].name
            const dataPoint = props.series[seriesIndex].data[dataPointIndex]
            if (dataPoint && dataPoint.x) {
              const slotStart = dataPoint.x as string
              // Calculate slot end from xCategories
              const nextIndex = dataPointIndex + 1
              const nextSlot = props.xCategories[nextIndex]
              let slotEnd: string
              if (nextSlot) {
                slotEnd = nextSlot
              } else if (dataPointIndex > 0 && props.xCategories[dataPointIndex - 1]) {
                // Last slot -- derive bin size from previous slot
                const prevSlot = props.xCategories[dataPointIndex - 1]!
                const binMs = new Date(slotStart).getTime() - new Date(prevSlot).getTime()
                slotEnd = new Date(new Date(slotStart).getTime() + binMs).toISOString()
              } else {
                // Single slot -- fallback +1h
                slotEnd = new Date(new Date(slotStart).getTime() + 3600000).toISOString()
              }
              const nodeId = props.series[seriesIndex]._nodeId as string | undefined
              const nodeIds = props.series[seriesIndex]._nodeIds as string[] | undefined
              emit('heatmapClick', { name, nodeId, nodeIds, timeSlot: slotStart, timeSlotEnd: slotEnd })
            }
          }
        },
      },
    },
    plotOptions: {
      heatmap: {
        enableShades: false,
        colorScale: {
          ranges: allRanges,
          inverse: false,
          min: 0,
          max: Math.max(1000 + maxFailed, 100),
        },
        radius: 0,
        useFillColorAsStroke: false,
        stroke: {
          width: 0,
        },
      },
    },
    grid: {
      show: false,
      borderColor: colors.background,
      padding: {
        left: 16,
        right: 0,
        top: 0,
        bottom: 0,
      },
    },
    dataLabels: {
      enabled: false,
    },
    legend: {
      show: false,
    },
    xaxis: {
      type: 'category',
      categories: props.xCategories,
      floating: false,
      labels: {
        show: true,
        rotate: -45,
        rotateAlways: false,
        trim: true,
        maxHeight: 80,
        hideOverlappingLabels: true,
        showDuplicates: false,
        style: {
          colors: colors.text,
          fontSize: '11px',
          fontFamily: 'Inter, sans-serif',
        },
        formatter: (value: string | undefined) => {
          if (!value || typeof value !== 'string') return ''

          const index = props.xCategories.indexOf(value)
          if (index !== -1 && index % 4 !== 0 && index !== props.xCategories.length - 1) {
            return ''
          }

          const dateObj = new Date(value)
          if (!isNaN(dateObj.getTime())) {
            const day = dateObj.getDate()
            const month = dateObj.getMonth() + 1
            const hours = dateObj.getHours()
            const minutes = String(dateObj.getMinutes()).padStart(2, '0')
            return `${day}.${month}. ${hours}:${minutes}`
          }
          return value
        },
      },
      tooltip: {
        enabled: false,
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
        show: true,
        style: {
          colors: colors.text,
          fontSize: '11px',
        },
        minWidth: 180,
        maxWidth: 180,
        formatter: (props.yLabelMap || props.yLabelPrefix)
          ? (val: string) => {
              const displayName = props.yLabelMap?.[val] || val
              const prefix = props.yLabelPrefix?.[displayName]
              return prefix ? [prefix, displayName] : displayName
            }
          : undefined,
      },
    },
    tooltip: {
      shared: false,
      followCursor: true,
      fillSeriesColor: true,
      style: {
        fontSize: '14px',
        fontFamily: 'Inter, sans-serif',
      },
      custom: ({ seriesIndex, dataPointIndex, w }: any) => {
        const data = w.config.series[seriesIndex].data[dataPointIndex]
        if (!data || !data.meta) return ''

        const { success, failed } = data.meta
        const rawSeriesName = w.config.series[seriesIndex].name
        const displayName = props.yLabelMap?.[rawSeriesName] || rawSeriesName
        const prefix = props.yLabelPrefix?.[displayName]
        const seriesName = prefix ? `${prefix} / ${displayName}` : displayName
        const rawTimeSlot = data.x || ''
        let timeSlot = rawTimeSlot
        const d = new Date(rawTimeSlot)
        if (!isNaN(d.getTime())) {
          timeSlot = `${d.getDate()}.${d.getMonth() + 1}. ${d.getHours()}:${String(d.getMinutes()).padStart(2, '0')}`
        }

        const bg = dark ? '#1f2937' : '#ffffff'
        const textPrimary = dark ? '#f9fafb' : '#111827'
        const textSecondary = dark ? '#9ca3af' : '#6b7280'
        const greenColor = dark ? '#4ade80' : '#16a34a'
        const redColor = dark ? '#f87171' : '#dc2626'

        const successText = success > 0
          ? `<span style="color:${greenColor};font-weight:500;">${success} success</span>`
          : ''
        const failedText = failed > 0
          ? `<span style="color:${redColor};font-weight:500;">${failed} failed</span>`
          : ''

        const countsText = [successText, failedText].filter(Boolean).join(' / ')

        return `
          <div style="background:${bg};border-radius:8px;padding:12px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.3);border:none;">
            <div style="font-size:13px;font-weight:500;color:${textPrimary};margin-bottom:4px;">
              ${seriesName}
            </div>
            <div style="font-size:12px;color:${textSecondary};margin-bottom:4px;">
              ${timeSlot}
            </div>
            <div style="font-size:12px;">
              ${countsText || `<span style="color:${textSecondary};">${emptyLabel}</span>`}
            </div>
          </div>
        `
      },
    },
  }
}

const handleFilterChange = (filter: ProcessFilter) => {
  activeFilter.value = filter
  emit('filterChange', filter)
}

watch(
  [() => props.series, () => props.yLabelMap, () => props.yLabelPrefix],
  () => {
    if (chartEl.value && props.series.length > 0) {
      nextTick(() => {
        initChart(chartEl.value!, getHeatmapOptions())
        setupResizeObserver(chartEl.value!)
      })
    }
  },
)

onMounted(() => {
  nextTick(() => {
    if (chartEl.value && props.series.length > 0) {
      initChart(chartEl.value, getHeatmapOptions())
      setupResizeObserver(chartEl.value)
      chartMounted.value = true
    }
  })
})
</script>

<template>
  <Card>
    <div class="mb-4 grid items-start gap-6 sm:mb-0" :class="showFilter ? 'grid-cols-3' : 'grid-cols-2'">
      <div>
        <h2 class="mb-2 text-xl font-bold leading-none text-gray-900 dark:text-white">
          {{ title }}
        </h2>
        <p v-if="timeRange" class="text-gray-500 dark:text-gray-400">{{ timeRange }}</p>
      </div>

      <!-- Metrics -->
      <div class="ml-auto grid grid-cols-2 gap-8">
        <div>
          <h3 class="mb-2 text-gray-500 dark:text-gray-400">{{ totalLabel }}</h3>
          <p class="text-2xl font-bold leading-none text-gray-900 dark:text-white">
            {{ totalCount.toLocaleString() }}
          </p>
        </div>
        <div>
          <h3 class="mb-2 text-gray-500 dark:text-gray-400">Failed</h3>
          <p class="text-2xl font-bold leading-none text-red-600 dark:text-red-400">
            {{ totalFailed.toLocaleString() }}
          </p>
        </div>
      </div>

      <!-- Filter Radio Buttons -->
      <div v-if="showFilter" class="flex items-center justify-end gap-4 self-end">
        <div class="flex items-center">
          <input
            :id="radioAllId"
            :name="radioName"
            type="radio"
            value="all"
            v-model="activeFilter"
            @change="handleFilterChange('all')"
            class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label
            :for="radioAllId"
            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
          >
            All
          </label>
        </div>
        <div class="flex items-center">
          <input
            :id="radioFailedId"
            :name="radioName"
            type="radio"
            value="failed"
            v-model="activeFilter"
            @change="handleFilterChange('failed')"
            class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label
            :for="radioFailedId"
            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
          >
            Failed
          </label>
        </div>
      </div>
    </div>

    <div class="w-full">
      <div ref="chartEl"></div>
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
