<script setup lang="ts">
import { ref } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import TabPanel from '@/components/ui/TabPanel.vue'
import TimeFilter from '@/components/ui/TimeFilter.vue'
import OverviewTab from '@/components/dashboard/OverviewTab.vue'
import ConnectorsTab from '@/components/dashboard/ConnectorsTab.vue'
import TopologiesTab from '@/components/dashboard/TopologiesTab.vue'
import type { Tab } from '@/components/ui/Tabs.vue'
import type { TimeFilter as TimeFilterType } from '@/types/dashboard'

// Tabs configuration
const dashboardTabs: Tab[] = [
  { id: 'overview', label: 'Overview', target: 'overview-content' },
  { id: 'connectors', label: 'Connectors', target: 'connectors-content' },
  { id: 'topologies', label: 'Topologies', target: 'topologies-content' },
  { id: 'processes', label: 'Processes', target: 'processes-content' },
]

// Time filter state
const activeTimeFilter = ref<TimeFilterType>('7d')
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
          >
            {{ tab.label }}
          </button>
        </li>
      </ul>

      <!-- Time Filter -->
      <TimeFilter v-model="activeTimeFilter" />
    </div>

    <!-- Tabs Content -->
    <div id="dashboard-tabs-content">
      <TabPanel id="overview-content" ariaLabelledby="overview-tab">
        <OverviewTab :time-filter="activeTimeFilter" />
      </TabPanel>

      <TabPanel id="connectors-content" ariaLabelledby="connectors-tab" :hidden="true">
        <ConnectorsTab :global-time-filter="activeTimeFilter" />
      </TabPanel>

      <TabPanel id="topologies-content" ariaLabelledby="topologies-tab" :hidden="true">
        <TopologiesTab />
      </TabPanel>

      <TabPanel id="processes-content" ariaLabelledby="processes-tab" :hidden="true">
        <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
          <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">Processes</h2>
          <p class="text-gray-500 dark:text-gray-400">
            Processes content will be implemented in next phase.
          </p>
        </div>
      </TabPanel>
    </div>
  </DashboardLayout>
</template>

