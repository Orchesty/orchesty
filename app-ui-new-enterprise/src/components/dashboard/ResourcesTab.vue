<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import Card from '@/components/ui/Card.vue'
import ResourceLimitCards from './ResourceLimitCards.vue'
import { useApexChart, getChartColors, getBaseChartOptions } from '@/composables/useApexChart'
import { useDateFormat } from '@/composables/useDateFormat'
import { fetchLimitsHistory, type CloudLimitsHistory } from '@/services/cloudLimitsService'
import { useCloudLimitsUsage } from '@/composables/useCloudLimitsUsage'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApi } from '@/utils/timeRangeConverter'
import type { TimeFilter } from '@/types/dashboard'

const { formatChartLabel } = useDateFormat()
const { usage } = useCloudLimitsUsage()

interface Props {
  timeFilter: TimeFilter
  refreshKey?: number
  // Render the 3 plan-limit cards (slots / messages / storage) above the
  // history charts. Enabled by the Starter dashboard, which has no Overview
  // tab to host them; Operations Suite leaves this off so the cards remain
  // exclusive to OverviewTab.
  showLimitCards?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  refreshKey: 0,
  showLimitCards: false,
})

const messagesChartEl = ref<HTMLElement | null>(null)
const storageChartEl = ref<HTMLElement | null>(null)
const messagesChartMounted = ref(false)
const storageChartMounted = ref(false)
const history = ref<CloudLimitsHistory | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)

const messagesChart = useApexChart({
  onDarkModeChange: () => {
    if (messagesChartMounted.value && messagesChartEl.value) {
      messagesChart.initChart(messagesChartEl.value, buildOptions('messages'))
    }
  },
})

const storageChart = useApexChart({
  onDarkModeChange: () => {
    if (storageChartMounted.value && storageChartEl.value) {
      storageChart.initChart(storageChartEl.value, buildOptions('storage'))
    }
  },
})

function granularity(timeFilter: TimeFilter): number {
  switch (timeFilter) {
    case '1h': return 5
    case '24h': return 60
    case '7d': return 720
    default: return 1440
  }
}

function buildOptions(kind: 'messages' | 'storage') {
  const isDark = kind === 'messages' ? messagesChart.isDarkMode.value : storageChart.isDarkMode.value
  const colors = getChartColors(isDark)
  const points = kind === 'messages' ? history.value?.messages ?? [] : history.value?.storage ?? []
  const categories = points.map((p) => p.created)
  const data = points.map((p) => p.value)
  const gran = granularity(props.timeFilter)
  const seriesName = kind === 'messages' ? 'Messages in flight' : 'Storage (MB)'
  const accent = kind === 'messages' ? colors.primary : colors.success ?? colors.primary

  return {
    ...getBaseChartOptions(isDark),
    series: [{ name: seriesName, data }],
    chart: {
      type: 'area',
      height: 280,
      toolbar: { show: false },
      background: 'transparent',
      animations: { enabled: false },
    },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.4,
        opacityTo: 0.05,
        stops: [0, 100],
      },
    },
    stroke: { show: true, width: 2, curve: 'smooth' },
    colors: [accent],
    dataLabels: { enabled: false },
    xaxis: {
      categories,
      labels: {
        style: { colors: colors.text, fontSize: '10px', fontFamily: 'Inter, sans-serif' },
        rotate: -45,
        hideOverlappingLabels: true,
        formatter: (value: string) => {
          if (!value) return ''
          const index = categories.indexOf(value)
          const step = Math.max(1, Math.floor(categories.length / 6))
          if (index !== -1 && index % step !== 0 && index !== categories.length - 1) return ''
          return formatChartLabel(value, gran)
        },
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      labels: {
        style: { colors: colors.text },
        formatter: (val: number) => kind === 'storage' ? `${val.toFixed(0)} MB` : `${val}`,
      },
    },
    tooltip: { theme: isDark ? 'dark' : 'light' },
  }
}

const summary = computed(() => {
  const u = usage.value
  return {
    messagesPercent: u?.percent.messages ?? null,
    storagePercent: u?.percent.storage ?? null,
    messagesCurrent: u?.usage.messages ?? 0,
    messagesLimit: u?.limits.messages ?? 0,
    storageMb: u?.usage.storageMb ?? 0,
    storageGb: u?.limits.storageGb ?? 0,
  }
})

async function loadData() {
  loading.value = true
  error.value = null
  try {
    const range = convertTimeFilterToDateTimeRange(props.timeFilter)
    const from = formatDateTimeForApi(range.from) || ''
    const to = formatDateTimeForApi(range.to) || ''
    history.value = await fetchLimitsHistory(from, to, 60)

    await nextTick()
    if (messagesChartEl.value) {
      messagesChart.initChart(messagesChartEl.value, buildOptions('messages'))
      messagesChartMounted.value = true
    }
    if (storageChartEl.value) {
      storageChart.initChart(storageChartEl.value, buildOptions('storage'))
      storageChartMounted.value = true
    }
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load resource history'
  } finally {
    loading.value = false
  }
}

watch(() => props.timeFilter, loadData)
watch(() => props.refreshKey, loadData)
onMounted(loadData)
</script>

<template>
  <div class="space-y-6">
    <ResourceLimitCards v-if="props.showLimitCards" />

    <div v-if="error" class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
      <p class="text-red-800 dark:text-red-400">{{ error }}</p>
    </div>

    <Card>
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Messages in flight</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ summary.messagesCurrent.toLocaleString() }}
            <span v-if="summary.messagesLimit > 0">
              / {{ summary.messagesLimit.toLocaleString() }}
            </span>
          </p>
        </div>
        <div v-if="summary.messagesPercent !== null" class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ summary.messagesPercent.toFixed(1) }}%
        </div>
      </div>
      <div class="relative h-72">
        <div ref="messagesChartEl" class="h-full"></div>
        <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/60 text-sm text-gray-500 dark:bg-gray-900/60">
          Loading…
        </div>
      </div>
    </Card>

    <Card>
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Storage</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ (summary.storageMb / 1024).toFixed(2) }} GB
            <span v-if="summary.storageGb > 0"> / {{ summary.storageGb }} GB</span>
          </p>
        </div>
        <div v-if="summary.storagePercent !== null" class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ summary.storagePercent.toFixed(1) }}%
        </div>
      </div>
      <div class="relative h-72">
        <div ref="storageChartEl" class="h-full"></div>
        <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/60 text-sm text-gray-500 dark:bg-gray-900/60">
          Loading…
        </div>
      </div>
    </Card>
  </div>
</template>
