<script setup lang="ts">
import HeatmapChart from './HeatmapChart.vue'
import type { ProcessFilter } from '@/types/dashboard'

interface Props {
  chartId?: string
  totalRequests: number
  totalFailed: number
  timeRange: string
  filter?: ProcessFilter
  series: any[]
  xCategories: string[]
  yLabelPrefix?: Record<string, string>
}

withDefaults(defineProps<Props>(), {
  chartId: 'default',
  filter: 'all',
})

const emit = defineEmits<{
  filterChange: [filter: ProcessFilter]
  heatmapClick: [data: { connector: string; timeSlot: string }]
}>()

const handleClick = (data: { name: string; timeSlot: string }) => {
  emit('heatmapClick', { connector: data.name, timeSlot: data.timeSlot })
}
</script>

<template>
  <HeatmapChart
    :chart-id="chartId"
    title="Applications over time"
    total-label="Requests"
    :total-count="totalRequests"
    :total-failed="totalFailed"
    :time-range="timeRange"
    empty-label="No requests"
    :filter="filter"
    :series="series"
    :x-categories="xCategories"
    :y-label-prefix="yLabelPrefix"
    @filter-change="emit('filterChange', $event)"
    @heatmap-click="handleClick"
  />
</template>
