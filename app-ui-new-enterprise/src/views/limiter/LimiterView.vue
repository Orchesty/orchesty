<script setup lang="ts">
import { ref, watch } from 'vue'
import TimeFilter from '@/components/ui/TimeFilter.vue'
import LimiterTab from '@/components/dashboard/LimiterTab.vue'
import AppRunningProcessesDrawer from '@/components/dashboard/AppRunningProcessesDrawer.vue'
import type { TimeFilter as TimeFilterType } from '@/types/dashboard'

const TIME_FILTER_KEY = 'orchesty_limiter_time_filter'
const savedTimeFilter = localStorage.getItem(TIME_FILTER_KEY) as TimeFilterType | null
const activeTimeFilter = ref<TimeFilterType>(savedTimeFilter || '24h')

const refreshKey = ref(0)
const refreshing = ref(false)

const handleRefresh = async () => {
  refreshing.value = true
  refreshKey.value++
  setTimeout(() => { refreshing.value = false }, 800)
}

watch(activeTimeFilter, (value) => {
  localStorage.setItem(TIME_FILTER_KEY, value)
})

const appProcessesDrawerOpen = ref(false)
const appProcessesAppId = ref<string | null>(null)
const appProcessesTopologyIds = ref<string[]>([])

const handleOpenAppProcesses = (data: { applicationId: string; topologyIds: string[] }) => {
  appProcessesAppId.value = data.applicationId
  appProcessesTopologyIds.value = data.topologyIds
  appProcessesDrawerOpen.value = true
}

const handleTerminated = () => {
  refreshKey.value++
}
</script>

<template>
  <main class="h-full overflow-y-auto">
    <div class="px-4 pb-4 pt-6">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Limiter</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Monitor rate limiting and message queues
          </p>
        </div>
        <div class="flex items-center gap-2">
          <TimeFilter v-model="activeTimeFilter" />
          <button
            type="button"
            title="Refresh"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:text-gray-900 focus:outline-hidden dark:text-gray-500 dark:hover:text-white"
            @click="handleRefresh"
          >
            <svg
              class="h-5 w-5 transition-transform duration-500"
              :class="{ 'animate-spin': refreshing }"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M1 4v6h6M23 20v-6h-6" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15" />
            </svg>
            <span class="sr-only">Refresh</span>
          </button>
        </div>
      </div>

      <LimiterTab
        :time-filter="activeTimeFilter"
        :refresh-key="refreshKey"
        @open-app-processes="handleOpenAppProcesses"
      />

      <AppRunningProcessesDrawer
        v-model="appProcessesDrawerOpen"
        :application-id="appProcessesAppId"
        :topology-ids="appProcessesTopologyIds"
        @terminated="handleTerminated"
      />
    </div>
  </main>
</template>
