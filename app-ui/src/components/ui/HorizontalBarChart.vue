<script setup lang="ts">
import { ref, onMounted, onActivated, nextTick, computed, watch } from 'vue'
import { useApexChart } from '@/composables/useApexChart'
import type { ApexOptions } from 'apexcharts'

export interface BarChartItem {
  label: string
  value: number
}

interface Props {
  data: BarChartItem[]
  seriesName?: string
  color?: string
}

const props = withDefaults(defineProps<Props>(), {
  seriesName: 'Time (ms)',
  color: '#0D9E58',
})

const chartEl = ref<HTMLElement | null>(null)
const ROW_HEIGHT = 36

const chartHeight = computed(() => (props.data.length * ROW_HEIGHT) + 60)

const chartData = computed(() =>
  props.data.map(item => ({ x: item.label, y: item.value })),
)

const getChartOptions = (): ApexOptions => ({
  colors: [props.color],
  series: [
    {
      name: props.seriesName,
      data: chartData.value,
    },
  ],
  chart: {
    type: 'bar',
    height: `${chartHeight.value}px`,
    fontFamily: 'Inter, sans-serif',
    toolbar: {
      show: false,
    },
  },
  plotOptions: {
    bar: {
      horizontal: true,
      barHeight: '75%',
      borderRadius: 3,
      borderRadiusApplication: 'end',
    },
  },
  tooltip: {
    shared: true,
    intersect: false,
    style: {
      fontSize: '14px',
      fontFamily: 'Inter, sans-serif',
    },
  },
  states: {
    hover: {
      filter: {
        type: 'darken',
      },
    },
  },
  stroke: {
    show: true,
    width: 0,
    colors: ['transparent'],
  },
  grid: {
    show: false,
  },
  dataLabels: {
    enabled: false,
  },
  legend: {
    show: false,
  },
  yaxis: {
    labels: {
      show: true,
      style: {
        fontFamily: 'Inter, sans-serif',
        cssClass: 'apexcharts-yaxis-label fill-gray-500 dark:fill-gray-400',
      },
    },
  },
  xaxis: {
    labels: {
      style: {
        fontFamily: 'Inter, sans-serif',
        cssClass: 'apexcharts-xaxis-label fill-gray-500 dark:fill-gray-400',
      },
    },
    axisBorder: {
      show: false,
    },
    axisTicks: {
      show: false,
    },
  },
  fill: {
    opacity: 1,
  },
})

const { initChart } = useApexChart()

const tryInitChart = () => {
  if (chartEl.value && props.data && props.data.length > 0) {
    initChart(chartEl.value, getChartOptions())
  }
}

onMounted(() => tryInitChart())

onActivated(() => nextTick(() => tryInitChart()))

watch(() => props.data, (newData) => {
  if (newData && newData.length > 0) {
    nextTick(() => tryInitChart())
  }
}, { deep: true })
</script>

<template>
  <div ref="chartEl"></div>
</template>
