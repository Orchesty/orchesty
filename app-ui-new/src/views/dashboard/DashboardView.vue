<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import TabPanel from '@/components/ui/TabPanel.vue'
import TimeFilter from '@/components/ui/TimeFilter.vue'
import OverviewTab from '@/components/dashboard/OverviewTab.vue'
import ConnectorsTab from '@/components/dashboard/ConnectorsTab.vue'
import TopologiesTab from '@/components/dashboard/TopologiesTab.vue'
import ProcessesTab from '@/components/dashboard/ProcessesTab.vue'
import LimiterTab from '@/components/dashboard/LimiterTab.vue'
import ApplicationsTab from '@/components/dashboard/ApplicationsTab.vue'
import ConnectorDetailDrawer from '@/components/dashboard/ConnectorDetailDrawer.vue'
import type { Tab } from '@/components/ui/Tabs.vue'
import type { Connector } from '@/types/connectors'
import type { TimeFilter as TimeFilterType, ProcessFilter, HeatmapClickData, ProcessesExternalFilters } from '@/types/dashboard'
import { formatDateTimeLocal } from '@/utils/timeRangeConverter'

// Tabs configuration
const dashboardTabs: Tab[] = [
  { id: 'overview', label: 'Overview', target: 'overview-content' },
  { id: 'applications', label: 'Applications', target: 'applications-content' },
  { id: 'connectors', label: 'Connectors', target: 'connectors-content' },
  { id: 'topologies', label: 'Topologies', target: 'topologies-content' },
  { id: 'processes', label: 'Processes', target: 'processes-content' },
  { id: 'limiter', label: 'Limiter', target: 'limiter-content' },
]

// Active tab persistence
const TAB_KEY = 'orchesty_dashboard_active_tab'
const savedTab = localStorage.getItem(TAB_KEY)

const handleTabClick = (tabId: string) => {
  localStorage.setItem(TAB_KEY, tabId)
}

const switchToTab = async (tabId: string) => {
  await nextTick()
  const tabButton = document.getElementById(`${tabId}-tab`)
  if (tabButton) {
    tabButton.click()
  }
}

onMounted(() => {
  // Restore saved tab after Flowbite initializes
  if (savedTab && savedTab !== 'overview') {
    nextTick(() => {
      const tabButton = document.getElementById(`${savedTab}-tab`)
      if (tabButton) {
        tabButton.click()
      }
    })
  }
})

// Time filter state - restore from localStorage
const TIME_FILTER_KEY = 'orchesty_dashboard_time_filter'
const savedTimeFilter = localStorage.getItem(TIME_FILTER_KEY) as TimeFilterType | null
const activeTimeFilter = ref<TimeFilterType>(savedTimeFilter || '7d')

// Refresh trigger - incremented to notify child tabs
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

// Heatmap filter state - shared between Overview and Processes tabs
const HEATMAP_FILTER_KEY = 'orchesty_dashboard_heatmap_filter'
const savedHeatmapFilter = localStorage.getItem(HEATMAP_FILTER_KEY) as ProcessFilter | null
const activeHeatmapFilter = ref<ProcessFilter>(savedHeatmapFilter || 'all')

const handleHeatmapFilterChange = (filter: ProcessFilter) => {
  activeHeatmapFilter.value = filter
  localStorage.setItem(HEATMAP_FILTER_KEY, filter)
}

// Processes external filters (set by heatmap click)
const processesFilters = ref<ProcessesExternalFilters>({
  topology: null,
  timeRange: null,
})

const handleHeatmapClick = async (data: HeatmapClickData) => {
  console.log('Heatmap clicked from Overview:', data)

  // Use exact slot boundaries from the heatmap
  const timeRange = {
    from: formatDateTimeLocal(new Date(data.timeSlot)),
    to: formatDateTimeLocal(new Date(data.timeSlotEnd)),
  }

  // Set filters for ProcessesTab
  processesFilters.value = {
    topology: data.topology,
    timeRange: timeRange,
  }

  // Check if we're not already on the Processes tab by checking if it has 'hidden' class
  const processesPanel = document.getElementById('processes-content')
  const isOnProcessesTab = processesPanel && !processesPanel.classList.contains('hidden')

  console.log('Is on Processes tab:', isOnProcessesTab)

  // Switch to processes tab only if we're not already there (i.e., we're on Overview)
  if (!isOnProcessesTab) {
    await nextTick()
    const processesTabButton = document.getElementById('processes-tab')
    console.log('Processes tab button:', processesTabButton)
    if (processesTabButton) {
      processesTabButton.click()
      console.log('Clicked processes tab button')
    }
  }
}

// Connector detail drawer (shared by ConnectorsTab and ApplicationsTab)
const connectorDrawerOpen = ref(false)
const selectedConnector = ref<Connector | null>(null)
const connectorNodeIds = ref<string[]>([])

const handleOpenConnectorDetail = (connector: Connector, nodeIds?: string[]) => {
  selectedConnector.value = connector
  connectorNodeIds.value = nodeIds || []
  connectorDrawerOpen.value = true
}

const handleTopologyProcessesClick = async (topologyId: string) => {
  console.log('Navigate to processes for topology:', topologyId)

  // Set topology filter for ProcessesTab
  processesFilters.value = {
    topology: topologyId,
    timeRange: null, // Don't apply time range filter from topology click
  }

  // Switch to processes tab
  await nextTick()
  const processesTabButton = document.getElementById('processes-tab')
  if (processesTabButton) {
    processesTabButton.click()
    console.log('Switched to processes tab with topology filter')
  }
}
</script>

<template>
  <DashboardLayout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Control Center</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Monitor and manage your processes
      </p>
    </div>

    <!-- Tabs with Time Filter -->
    <div class="mb-6 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
      <!-- Tabs Navigation -->
      <ul
        class="-mb-px flex flex-wrap text-center text-sm font-medium"
        id="dashboard-tabs"
        data-tabs-toggle="#dashboard-tabs-content"
        role="tablist"
        data-tabs-active-classes="text-primary-600 border-primary-600 dark:text-primary-500 dark:border-primary-500"
        data-tabs-inactive-classes="text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300"
      >
        <li v-for="tab in dashboardTabs" :key="tab.id" class="mr-2" role="presentation">
          <button
            class="inline-block rounded-t-lg border-b-2 p-4"
            :id="`${tab.id}-tab`"
            :data-tabs-target="`#${tab.target}`"
            type="button"
            role="tab"
            :aria-controls="tab.target"
            :aria-selected="tab.id === 'overview'"
            @click="handleTabClick(tab.id)"
          >
            {{ tab.label }}
          </button>
        </li>
      </ul>

      <!-- Time Filter + Refresh -->
      <div class="flex items-center gap-2">
        <TimeFilter v-model="activeTimeFilter" />
        <button
          type="button"
          title="Refresh"
          class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:text-gray-900 focus:outline-none dark:text-gray-500 dark:hover:text-white"
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

    <!-- Tabs Content -->
    <div id="dashboard-tabs-content">
      <TabPanel id="overview-content" ariaLabelledby="overview-tab">
        <OverviewTab
          :time-filter="activeTimeFilter"
          :heatmap-filter="activeHeatmapFilter"
          :refresh-key="refreshKey"
          @heatmap-click="handleHeatmapClick"
          @heatmap-filter-change="handleHeatmapFilterChange"
          @limiter-view-all="switchToTab('limiter')"
        />
      </TabPanel>

      <TabPanel id="applications-content" ariaLabelledby="applications-tab" :hidden="true">
        <ApplicationsTab
          :time-filter="activeTimeFilter"
          :heatmap-filter="activeHeatmapFilter"
          :refresh-key="refreshKey"
          @open-connector-detail="handleOpenConnectorDetail"
        />
      </TabPanel>

      <TabPanel id="connectors-content" ariaLabelledby="connectors-tab" :hidden="true">
        <ConnectorsTab
          :global-time-filter="activeTimeFilter"
          :refresh-key="refreshKey"
          @open-connector-detail="handleOpenConnectorDetail"
        />
      </TabPanel>

      <TabPanel id="topologies-content" ariaLabelledby="topologies-tab" :hidden="true">
        <TopologiesTab
          :global-time-filter="activeTimeFilter"
          :refresh-key="refreshKey"
          @view-processes="handleTopologyProcessesClick"
        />
      </TabPanel>

      <TabPanel id="processes-content" ariaLabelledby="processes-tab" :hidden="true">
        <ProcessesTab
          :global-time-filter="activeTimeFilter"
          :heatmap-filter="activeHeatmapFilter"
          :external-filters="processesFilters"
          :refresh-key="refreshKey"
          @heatmap-filter-change="handleHeatmapFilterChange"
        />
      </TabPanel>

      <TabPanel id="limiter-content" ariaLabelledby="limiter-tab" :hidden="true">
        <LimiterTab :global-time-filter="activeTimeFilter" :refresh-key="refreshKey" />
      </TabPanel>
    </div>
    <!-- Shared Connector Detail Drawer -->
    <ConnectorDetailDrawer
      v-model="connectorDrawerOpen"
      :connector="selectedConnector"
      :global-time-filter="activeTimeFilter"
      :node-ids="connectorNodeIds"
    />
  </DashboardLayout>
</template>

