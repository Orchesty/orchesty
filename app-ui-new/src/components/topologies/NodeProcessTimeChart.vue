<script setup lang="ts">
import { ref, onMounted, onActivated, nextTick, computed } from 'vue'
import { useApexChart } from '@/composables/useApexChart'
import type { ApexOptions } from 'apexcharts'

interface NodeProcessTime {
  nodeName: string
  time: number
}

interface Props {
  data: NodeProcessTime[]
}

const props = defineProps<Props>()

const chartEl = ref<HTMLElement | null>(null)
const ROW_HEIGHT = 36

const chartHeight = computed(() => (props.data.length * ROW_HEIGHT) + 60)

const chartData = computed(() => 
  props.data.map(item => ({ x: item.nodeName, y: item.time }))
)

const getChartOptions = (): ApexOptions => ({
  colors: ['#0D9E58'],
  series: [
    {
      name: 'Time (ms)',
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
        value: 1,
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

onMounted(() => {
  if (chartEl.value && props.data && props.data.length > 0) {
    initChart(chartEl.value, getChartOptions())
  }
})

onActivated(() => {
  nextTick(() => {
    if (chartEl.value && props.data && props.data.length > 0) {
      initChart(chartEl.value, getChartOptions())
    }
  })
})
</script>

<template>
  <div ref="chartEl"></div>
</template>

