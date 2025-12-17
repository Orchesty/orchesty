<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useApexChart, getChartColors, getBaseChartOptions } from '@/composables/useApexChart'
import type { TrashData, TableColumn } from '@/types/dashboard'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'

interface Props {
  data: TrashData
}

const props = defineProps<Props>()

const chartEl = ref<HTMLElement | null>(null)

const { initChart, setupResizeObserver, isDarkMode, chartInstance } = useApexChart({
  onDarkModeChange: () => {
    console.log('🗑️ TrashCard: Dark mode changed, re-rendering chart')
    // Re-render chart like original Flowbite template
    if (chartEl.value) {
      initChart(chartEl.value, getBarChartOptions())
      setupResizeObserver(chartEl.value)
    }
  },
})

const columns: TableColumn[] = [
  { key: 'topology', label: 'Topology' },
  { key: 'node', label: 'Node' },
  { key: 'message', label: 'Message' },
  { key: 'count', label: 'Count' },
]

const getBarChartOptions = () => {
  const colors = getChartColors(isDarkMode.value)

  return {
    ...getBaseChartOptions(isDarkMode.value),
    series: [
      {
        name: 'Messages',
        data: props.data.chartData,
      },
    ],
    chart: {
      type: 'bar',
      height: 256,
      toolbar: {
        show: false,
      },
      background: colors.background,
    },
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 0,
        barHeight: '70%',
      },
    },
    colors: [colors.primary],
    dataLabels: {
      enabled: false,
    },
    stroke: {
      show: true,
      width: 0,
      colors: ['transparent'],
    },
    states: {
      hover: {
        filter: {
          type: 'darken',
          value: 1,
        },
      },
    },
    xaxis: {
      labels: {
        style: {
          colors: colors.text,
          fontFamily: 'Inter, sans-serif',
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
      labels: {
        show: true,
        style: {
          colors: colors.text,
          fontFamily: 'Inter, sans-serif',
        },
      },
    },
    legend: {
      show: false,
    },
  }
}

onMounted(() => {
  nextTick(() => {
    if (chartEl.value) {
      initChart(chartEl.value, getBarChartOptions())
      setupResizeObserver(chartEl.value)
    }
  })
})
</script>

<template>
  <Card>
    <!-- Header with metrics -->
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Trash</h3>
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
      <template #cell-topology="{ value }">
        <span class="font-medium text-gray-900 dark:text-white">{{ value }}</span>
      </template>
      <template #cell-message="{ value }">
        <span class="max-w-xs truncate">{{ value }}</span>
      </template>
      <template #cell-count="{ value }">
        <span class="font-medium text-red-600 dark:text-red-400">{{ value }}</span>
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

