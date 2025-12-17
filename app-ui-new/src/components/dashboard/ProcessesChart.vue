<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useApexChart, getChartColors, getBaseChartOptions } from '@/composables/useApexChart'
import type { ProcessFilter } from '@/types/dashboard'
import Card from '@/components/ui/Card.vue'

interface Props {
  totalProcesses: number
  totalFailed: number
  timeRange: string
  filter?: ProcessFilter
  series: any[]
  xCategories: string[]
  yCategories: string[]
}

const props = withDefaults(defineProps<Props>(), {
  filter: 'all',
})

const emit = defineEmits<{
  filterChange: [filter: ProcessFilter]
}>()

const chartEl = ref<HTMLElement | null>(null)
const activeFilter = ref<ProcessFilter>(props.filter)

const { chartInstance, isDarkMode, initChart, setupResizeObserver } = useApexChart({
  onDarkModeChange: () => {
    // Re-render chart like original Flowbite template
    if (chartEl.value && props.series.length > 0) {
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
      const opacity = 0.15 + (i + 1) * 0.15 // 0.15 to 0.90
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

  return {
    ...getBaseChartOptions(isDarkMode.value),
    series: props.series,
    chart: {
      id: 'overview-processes-heatmap',
      width: '100%',
      height: 350,
      type: 'heatmap',
      background: colors.background,
      redrawOnWindowResize: true,
      redrawOnParentResize: true,
      parentHeightOffset: 0,
      toolbar: {
        show: false,
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
          
          // Show only every 4th label to reduce clutter
          const index = props.xCategories.indexOf(value)
          
          // Show every 4th label (index 0, 4, 8, 12, ...) and the last one
          if (index !== -1 && index % 4 !== 0 && index !== props.xCategories.length - 1) {
            return ''
          }
          
          // Format: "YYYY-MM-DD HH:00" -> "MM/DD HH:00"
          const parts = value.split(' ')
          if (parts.length === 2) {
            const [date, time] = parts
            if (!date || !time) return value
            const dateObj = new Date(date)
            const month = String(dateObj.getMonth() + 1).padStart(2, '0')
            const day = String(dateObj.getDate()).padStart(2, '0')
            return `${month}/${day} ${time}`
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
        const topology = w.config.series[seriesIndex].name
        const timeSlot = data.x || ''

        const successText = success > 0 
          ? `<span class="text-green-600 dark:text-green-400 font-medium">${success} success</span>`
          : ''
        const failedText = failed > 0
          ? `<span class="text-red-600 dark:text-red-400 font-medium">${failed} failed</span>`
          : ''
        
        const countsText = [successText, failedText].filter(Boolean).join(' / ')

        return `
          <div class="rounded-lg bg-white dark:bg-gray-800 shadow-lg p-3">
            <div class="text-sm font-medium text-gray-900 dark:text-white mb-1">
              ${topology}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
              ${timeSlot}
            </div>
            <div class="text-xs mt-2">
              ${countsText || '<span class="text-gray-400 dark:text-gray-500">No processes</span>'}
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
  () => props.series,
  () => {
    // Like original: destroy and re-create chart on series change
    if (chartEl.value && props.series.length > 0) {
      nextTick(() => {
        initChart(chartEl.value!, getHeatmapOptions())
        setupResizeObserver(chartEl.value!)
      })
    }
  },
)

onMounted(() => {
  // Initialize only when element is ready and has data
  nextTick(() => {
    if (chartEl.value && props.series.length > 0) {
      initChart(chartEl.value, getHeatmapOptions())
      setupResizeObserver(chartEl.value)
    }
  })
})
</script>

<template>
  <Card>
    <div class="mb-4 grid grid-cols-3 items-start gap-6 sm:mb-0">
      <div>
        <h2 class="mb-2 text-xl font-bold leading-none text-gray-900 dark:text-white">
          Processes over time
        </h2>
        <p class="text-gray-500 dark:text-gray-400">{{ timeRange }}</p>
      </div>

      <!-- Metrics -->
      <div class="mx-auto grid grid-cols-2 gap-8">
        <div>
          <h3 class="mb-2 text-gray-500 dark:text-gray-400">Processes</h3>
          <p class="text-2xl font-bold leading-none text-gray-900 dark:text-white">
            {{ totalProcesses.toLocaleString() }}
          </p>
        </div>
        <div>
          <h3 class="mb-2 text-gray-500 dark:text-gray-400">Failed</h3>
          <p class="text-2xl font-bold leading-none text-red-600 dark:text-red-400">
            {{ totalFailed }}
          </p>
        </div>
      </div>

      <!-- Filter Radio Buttons -->
      <div class="flex items-center justify-end gap-4 self-end">
        <div class="flex items-center">
          <input
            id="overview-process-filter-all"
            name="overview-process-filter"
            type="radio"
            value="all"
            :checked="activeFilter === 'all'"
            @change="handleFilterChange('all')"
            class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label
            for="overview-process-filter-all"
            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
          >
            All
          </label>
        </div>
        <div class="flex items-center">
          <input
            id="overview-process-filter-failed"
            name="overview-process-filter"
            type="radio"
            value="failed"
            :checked="activeFilter === 'failed'"
            @change="handleFilterChange('failed')"
            class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <label
            for="overview-process-filter-failed"
            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
          >
            Failed
          </label>
        </div>
      </div>
    </div>

    <div id="overview-processes-chart-wrapper" class="w-full dark:bg-gray-800">
      <div id="overview-processes-chart" ref="chartEl" class="h-64"></div>
    </div>
  </Card>
</template>

