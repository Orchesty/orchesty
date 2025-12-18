<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useApexChart, getChartColors, getBaseChartOptions } from '@/composables/useApexChart'
import type { LimiterData, TableColumn } from '@/types/dashboard'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'

interface Props {
  data: LimiterData
}

const props = defineProps<Props>()

const chartEl = ref<HTMLElement | null>(null)
const chartMounted = ref(false)

const { initChart, setupResizeObserver, isDarkMode, chartInstance } = useApexChart({
  onDarkModeChange: () => {
    // Re-render chart like original Flowbite template
    // Only if chart was already mounted
    if (chartMounted.value && chartEl.value) {
      initChart(chartEl.value, getColumnChartOptions())
      setupResizeObserver(chartEl.value)
    }
  },
})

// Initialize chart on mount
onMounted(async () => {
  try {
    await nextTick()
    if (!chartEl.value) {
      return
    }
    
    initChart(chartEl.value, getColumnChartOptions())
    setupResizeObserver(chartEl.value)
    chartMounted.value = true
  } catch (error) {
    console.error('LimiterCard mount error:', error)
  }
})

const columns: TableColumn[] = [
  { key: 'connector', label: 'Connector' },
  { key: 'topology', label: 'Topology' },
  { key: 'messages', label: 'Messages' },
]

const getColumnChartOptions = () => {
  const colors = getChartColors(isDarkMode.value)

  return {
    ...getBaseChartOptions(isDarkMode.value),
    series: [
      {
        name: 'Messages',
        data: props.data.chartData.series,
      },
    ],
    chart: {
      type: 'area',
      height: 256,
      toolbar: {
        show: false,
      },
      background: colors.background,
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
      categories: props.data.chartData.categories,
      labels: {
        show: false,
        style: {
          colors: colors.text,
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
  }
}

onMounted(() => {
  nextTick(() => {
    if (chartEl.value) {
      initChart(chartEl.value, getColumnChartOptions())
      setupResizeObserver(chartEl.value)
    }
  })
})
</script>

<template>
  <Card>
    <!-- Header with metrics -->
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Limiter</h3>
      <div class="flex items-center gap-6">
        <div class="flex flex-col items-center">
          <span class="text-xs text-gray-500 dark:text-gray-400">messages</span>
          <span class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ data.totalMessages }}
          </span>
        </div>
        <div class="flex flex-col items-center">
          <span class="text-xs text-gray-500 dark:text-gray-400">vs last day</span>
          <div class="flex items-center gap-1">
            <svg
              class="h-6 w-6 text-green-600 dark:text-green-400"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 19V5m0 14-4-4m4 4 4-4"
              />
            </svg>
            <span class="text-2xl font-bold text-green-600 dark:text-green-400">
              {{ data.vsLastDay }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div class="relative mb-4 h-64 overflow-hidden">
      <div ref="chartEl" class="h-full"></div>
    </div>

    <!-- Table -->
    <DataGrid :columns="columns" :data="data.tableData">
      <template #cell-connector="{ value }">
        <span class="font-medium text-gray-900 dark:text-white">{{ value }}</span>
      </template>
      <template #cell-messages="{ row }">
        {{ row.messages }} (+{{ row.change }}%)
      </template>
    </DataGrid>

    <div class="mt-4">
      <a
        href="#"
        class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-500"
      >
        View all →
      </a>
    </div>
  </Card>
</template>

