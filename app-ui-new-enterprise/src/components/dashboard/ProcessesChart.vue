<script setup lang="ts">
import HeatmapChart from './HeatmapChart.vue'
import type { ProcessFilter } from '@/types/dashboard'

interface Props {
  chartId?: string
  totalProcesses: number
  totalFailed: number
  timeRange: string
  filter?: ProcessFilter
  series: any[]
  xCategories: string[]
  yLabelMap?: Record<string, string>
}

withDefaults(defineProps<Props>(), {
  chartId: 'default',
  filter: 'all',
})

const emit = defineEmits<{
  filterChange: [filter: ProcessFilter]
  heatmapClick: [data: { topology: string; timeSlot: string; timeSlotEnd: string }]
}>()

const handleClick = (data: { name: string; timeSlot: string; timeSlotEnd: string }) => {
  emit('heatmapClick', { topology: data.name, timeSlot: data.timeSlot, timeSlotEnd: data.timeSlotEnd })
}
</script>

<template>
  <HeatmapChart
    :chart-id="chartId"
    title="Processes over time"
    total-label="Processes"
    :total-count="totalProcesses"
    :total-failed="totalFailed"
    :time-range="timeRange"
    empty-label="No processes"
    :filter="filter"
    :series="series"
    :x-categories="xCategories"
    :y-label-map="yLabelMap"
    @filter-change="emit('filterChange', $event)"
    @heatmap-click="handleClick"
  />
</template>
