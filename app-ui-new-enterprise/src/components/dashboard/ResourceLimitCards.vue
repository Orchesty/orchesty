<script setup lang="ts">
import { computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import { useCloudLimitsUsage } from '@/composables/useCloudLimitsUsage'
import type { CloudLimitBand } from '@/services/cloudLimitsService'

const { usage, error } = useCloudLimitsUsage()

interface CardModel {
  id: 'slots' | 'messages' | 'storage'
  title: string
  primary: string
  secondary: string
  percent: number | null
  band: CloudLimitBand
  showPercent: boolean
}

function bandColor(band: CloudLimitBand): { bar: string; text: string } {
  switch (band) {
    case 'exceeded':
    case 'critical':
      return { bar: 'bg-red-500', text: 'text-red-600 dark:text-red-400' }
    case 'warning':
      return { bar: 'bg-yellow-500', text: 'text-yellow-600 dark:text-yellow-400' }
    default:
      return { bar: 'bg-primary-600', text: 'text-gray-900 dark:text-white' }
  }
}

function formatPercent(value: number | null): string {
  if (value === null || Number.isNaN(value)) return '—'
  return `${value.toFixed(1)}%`
}

const cards = computed<CardModel[]>(() => {
  const u = usage.value
  if (!u) {
    return [
      { id: 'slots', title: 'Topology slots', primary: '—', secondary: 'Loading…', percent: null, band: 'none', showPercent: false },
      { id: 'messages', title: 'Messages in flight', primary: '—', secondary: 'Loading…', percent: null, band: 'none', showPercent: true },
      { id: 'storage', title: 'Storage', primary: '—', secondary: 'Loading…', percent: null, band: 'none', showPercent: true },
    ]
  }

  const slotsUsed = u.usage.topologySlots
  const slotsLimit = u.limits.topologySlots
  const slotsLabel = slotsLimit > 0 ? `${slotsUsed} / ${slotsLimit}` : `${slotsUsed} / ∞`

  const messagesUsed = u.usage.messages
  const messagesLimit = u.limits.messages
  const messagesPercent = u.percent.messages

  const storageMb = u.usage.storageMb
  const storageLimitGb = u.limits.storageGb
  const storageUsedGb = storageMb / 1024
  const storagePercent = u.percent.storage

  return [
    {
      id: 'slots',
      title: 'Topology slots',
      primary: slotsLabel,
      secondary: slotsLimit > 0 ? 'Used / available' : 'Unlimited plan',
      percent: slotsLimit > 0 ? (slotsUsed / slotsLimit) * 100 : null,
      band: 'none',
      showPercent: false,
    },
    {
      id: 'messages',
      title: 'Messages in flight',
      primary: messagesLimit > 0 ? formatPercent(messagesPercent) : `${messagesUsed.toLocaleString()}`,
      secondary: messagesLimit > 0
        ? `${messagesUsed.toLocaleString()} / ${messagesLimit.toLocaleString()}`
        : 'Unlimited plan',
      percent: messagesPercent,
      band: u.band.messages,
      showPercent: true,
    },
    {
      id: 'storage',
      title: 'Storage',
      primary: storageLimitGb > 0 ? formatPercent(storagePercent) : `${storageUsedGb.toFixed(2)} GB`,
      secondary: storageLimitGb > 0
        ? `${storageUsedGb.toFixed(2)} / ${storageLimitGb} GB`
        : 'Unlimited plan',
      percent: storagePercent,
      band: u.band.storage,
      showPercent: true,
    },
  ]
})
</script>

<template>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <Card v-for="card in cards" :key="card.id">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ card.title }}</p>
          <p
            class="mt-1 text-3xl font-bold"
            :class="bandColor(card.band).text"
          >
            {{ card.primary }}
          </p>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ card.secondary }}</p>
        </div>
      </div>
      <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
        <div
          class="h-full rounded-full transition-all duration-300"
          :class="bandColor(card.band).bar"
          :style="{ width: `${Math.min(100, Math.max(0, card.percent ?? 0))}%` }"
        ></div>
      </div>
    </Card>

    <p v-if="error" class="col-span-full text-xs text-red-600 dark:text-red-400">
      Failed to load cloud limits: {{ error }}
    </p>
  </div>
</template>
