<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { AlertTriangle, X } from 'lucide-vue-next'
import { useCloudLimitsUsage } from '@/composables/useCloudLimitsUsage'
import type { CloudLimitBand } from '@/services/cloudLimitsService'

const { usage, highestBand } = useCloudLimitsUsage()

const dismissed = ref<CloudLimitBand>('none')

watch(highestBand, (band) => {
  if (band !== dismissed.value) dismissed.value = 'none'
})

interface BannerModel {
  severity: 'warning' | 'critical'
  title: string
  detail: string
}

function offendingResources(threshold: 'critical' | 'exceeded') {
  const u = usage.value
  if (!u) return [] as Array<{ resource: 'messages' | 'storage'; percent: number | null }>
  const out: Array<{ resource: 'messages' | 'storage'; percent: number | null }> = []
  if (threshold === 'exceeded') {
    if (u.band.messages === 'exceeded') out.push({ resource: 'messages', percent: u.percent.messages })
    if (u.band.storage === 'exceeded') out.push({ resource: 'storage', percent: u.percent.storage })
  } else {
    if (u.band.messages === 'critical' || u.band.messages === 'exceeded') {
      out.push({ resource: 'messages', percent: u.percent.messages })
    }
    if (u.band.storage === 'critical' || u.band.storage === 'exceeded') {
      out.push({ resource: 'storage', percent: u.percent.storage })
    }
  }
  return out
}

function describe(resource: 'messages' | 'storage'): string {
  return resource === 'messages' ? 'messages in flight' : 'storage'
}

const banner = computed<BannerModel | null>(() => {
  if (highestBand.value === 'none' || highestBand.value === 'warning') return null
  if (dismissed.value !== 'none' && dismissed.value === highestBand.value) return null

  if (highestBand.value === 'exceeded') {
    const items = offendingResources('exceeded')
    const list = items.map((i) => `${describe(i.resource)} (${i.percent !== null ? `${i.percent.toFixed(0)}%` : '>=100%'})`).join(', ')
    return {
      severity: 'critical',
      title: 'Cloud plan limit exceeded',
      detail: `Limit exceeded for ${list || 'a monitored resource'}. Bridges may be discarding messages to protect the platform — review your plan immediately.`,
    }
  }

  // critical (>=90%)
  const items = offendingResources('critical')
  const list = items.map((i) => `${describe(i.resource)} (${i.percent !== null ? `${i.percent.toFixed(0)}%` : '>=90%'})`).join(', ')
  return {
    severity: 'warning',
    title: 'Approaching cloud plan limit',
    detail: `${list || 'A monitored resource'} is approaching the plan ceiling. Exceeding the limit may cause message loss — consider upgrading or reducing load.`,
  }
})

function dismiss() {
  dismissed.value = highestBand.value
}
</script>

<template>
  <div
    v-if="banner"
    :class="[
      'flex items-start gap-3 border-b px-4 py-2 text-sm',
      banner.severity === 'critical'
        ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-800/40 dark:bg-red-900/30 dark:text-red-200'
        : 'border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-800/40 dark:bg-yellow-900/30 dark:text-yellow-200',
    ]"
    role="alert"
  >
    <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
    <div class="flex-1">
      <span class="font-semibold">{{ banner.title }}.</span>
      <span class="ml-1">{{ banner.detail }}</span>
    </div>
    <button
      type="button"
      class="shrink-0 rounded p-0.5 hover:bg-black/5 dark:hover:bg-white/10"
      @click="dismiss"
    >
      <X class="h-3.5 w-3.5" />
      <span class="sr-only">Dismiss banner</span>
    </button>
  </div>
</template>
