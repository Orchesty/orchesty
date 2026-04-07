<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import TimeFilter from '@/components/ui/TimeFilter.vue'
import OverviewTab from '@/components/dashboard/OverviewTab.vue'
import ConnectorsTab from '@/components/dashboard/ConnectorsTab.vue'
import TopologiesTab from '@/components/dashboard/TopologiesTab.vue'
import ProcessesTab from '@/components/dashboard/ProcessesTab.vue'
import ApplicationsTab from '@/components/dashboard/ApplicationsTab.vue'
import ConnectorDetailDrawer from '@/components/dashboard/ConnectorDetailDrawer.vue'
import ProcessesDrawer from '@/components/dashboard/ProcessesDrawer.vue'
import ProcessAuditDrawer from '@/components/dashboard/ProcessAuditDrawer.vue'
import { useFeatures } from '@/composables/useFeatures'
import type { Connector } from '@/types/connectors'
import type { Process, ProcessConnector } from '@/types/processes'
import type { TimeFilter as TimeFilterType, ProcessFilter, HeatmapClickData, ProcessesExternalFilters } from '@/types/dashboard'
import { formatDateTimeLocal } from '@/utils/timeRangeConverter'

const router = useRouter()
const { enterpriseDashboards } = useFeatures()

const dashboardTabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'applications', label: 'Applications' },
  { id: 'connectors', label: 'Connectors' },
  { id: 'topologies', label: 'Topologies' },
  { id: 'processes', label: 'Processes' },
]

// Active tab state -- Vue-controlled, persisted in localStorage
const TAB_KEY = 'orchesty_dashboard_active_tab'
const savedTab = localStorage.getItem(TAB_KEY)
const activeTab = ref(savedTab || 'overview')

const handleTabClick = (tabId: string) => {
  activeTab.value = tabId
  localStorage.setItem(TAB_KEY, tabId)
}

const switchToTab = (tabId: string) => {
  activeTab.value = tabId
  localStorage.setItem(TAB_KEY, tabId)
}

const activeTabClass = 'text-primary-600 border-primary-600 dark:text-primary-500 dark:border-primary-500'
const inactiveTabClass = 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'

// Time filter state - restore from localStorage
const TIME_FILTER_KEY = 'orchesty_dashboard_time_filter'
const savedTimeFilter = localStorage.getItem(TIME_FILTER_KEY) as TimeFilterType | null
const activeTimeFilter = ref<TimeFilterType>(savedTimeFilter || '24h')

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

const handleHeatmapClick = (data: HeatmapClickData) => {
  processesDrawerTopology.value = data.topology
  processesDrawerTimeRange.value = {
    from: formatDateTimeLocal(new Date(data.timeSlot)),
    to: formatDateTimeLocal(new Date(data.timeSlotEnd)),
  }
  processesDrawerOpen.value = true
}

// Processes drawer state (opened from heatmap click)
const processesDrawerOpen = ref(false)
const processesDrawerTopology = ref<string | null>(null)
const processesDrawerTimeRange = ref<{ from: string; to: string } | null>(null)

// Audit drawer state (opened from processes drawer magnifier)
const auditDrawerOpen = ref(false)
const auditDrawerProcess = ref<Process | null>(null)
const pendingAuditOpen = ref(false)
const pendingProcessesOpen = ref(false)

const handleOpenAudit = (process: Process) => {
  auditDrawerProcess.value = process
  pendingAuditOpen.value = true
  processesDrawerOpen.value = false
}

const onProcessesDrawerHidden = () => {
  if (pendingAuditOpen.value) {
    pendingAuditOpen.value = false
    auditDrawerOpen.value = true
  }
}

const handleAuditBack = () => {
  pendingProcessesOpen.value = true
  auditDrawerOpen.value = false
}

const pendingConnectorFromAudit = ref(false)
const pendingAuditFromConnector = ref(false)
const connectorFromAudit = ref(false)

const onAuditDrawerHidden = () => {
  if (pendingProcessesOpen.value) {
    pendingProcessesOpen.value = false
    processesDrawerOpen.value = true
  }
  if (pendingConnectorFromAudit.value) {
    pendingConnectorFromAudit.value = false
    connectorFromAudit.value = true
    connectorDrawerOpen.value = true
  }
}

const handleAuditOpenConnector = (processConnector: ProcessConnector) => {
  selectedConnector.value = {
    nodeIds: [processConnector.connector],
    name: processConnector.connector,
    application: processConnector.application,
    topologyIds: [],
    avgRequestTime: 0,
    requests: processConnector.called,
    errors400: processConnector.errors400,
    errors500: processConnector.errors500,
    lastRequestStatus: 0,
    status: processConnector.errors400 + processConnector.errors500 > 0 ? 'errors' : 'ok',
  }
  pendingConnectorFromAudit.value = true
  auditDrawerOpen.value = false
}

const handleConnectorBackToAudit = () => {
  pendingAuditFromConnector.value = true
  connectorDrawerOpen.value = false
}

const onConnectorDrawerHidden = () => {
  if (pendingAuditFromConnector.value) {
    pendingAuditFromConnector.value = false
    connectorFromAudit.value = false
    auditDrawerOpen.value = true
  }
  connectorFromAudit.value = false
}

// Connector detail drawer (shared by ConnectorsTab, ApplicationsTab, and ProcessAuditDrawer)
const connectorDrawerOpen = ref(false)
const selectedConnector = ref<Connector | null>(null)

const handleOpenConnectorDetail = (connector: Connector) => {
  connectorFromAudit.value = false
  selectedConnector.value = connector
  connectorDrawerOpen.value = true
}

const handleTopologyProcessesClick = (topologyId: string) => {
  processesFilters.value = {
    topology: topologyId,
    timeRange: null,
  }

  switchToTab('processes')
}

</script>

<template>
  <!-- Core-only view: Process grid -->
  <main v-if="!enterpriseDashboards" class="h-full overflow-y-auto">
    <div class="px-4 pb-4 pt-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Processes</h1>
      </div>
      <ProcessesTab time-filter="24h" />
    </div>
  </main>

  <!-- Enterprise dashboard -->
  <main v-else class="h-full overflow-y-auto"><div class="px-4 pb-4 pt-6">
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
        role="tablist"
      >
        <li v-for="tab in dashboardTabs" :key="tab.id" class="mr-2" role="presentation">
          <button
            class="inline-block rounded-t-lg border-b-2 p-4"
            :class="activeTab === tab.id ? activeTabClass : inactiveTabClass"
            type="button"
            role="tab"
            :aria-selected="activeTab === tab.id"
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

    <!-- Tabs Content -->
    <KeepAlive>
      <OverviewTab
        v-if="activeTab === 'overview'"
        :time-filter="activeTimeFilter"
        :heatmap-filter="activeHeatmapFilter"
        :refresh-key="refreshKey"
        @heatmap-click="handleHeatmapClick"
        @heatmap-filter-change="handleHeatmapFilterChange"
        @limiter-view-all="router.push('/limiter')"
      />

      <ApplicationsTab
        v-else-if="activeTab === 'applications'"
        :time-filter="activeTimeFilter"
        :heatmap-filter="activeHeatmapFilter"
        :refresh-key="refreshKey"
        @open-connector-detail="handleOpenConnectorDetail"
      />

      <ConnectorsTab
        v-else-if="activeTab === 'connectors'"
        :time-filter="activeTimeFilter"
        :refresh-key="refreshKey"
        @open-connector-detail="handleOpenConnectorDetail"
      />

      <TopologiesTab
        v-else-if="activeTab === 'topologies'"
        :time-filter="activeTimeFilter"
        :refresh-key="refreshKey"
        @view-processes="handleTopologyProcessesClick"
      />

      <ProcessesTab
        v-else-if="activeTab === 'processes'"
        :time-filter="activeTimeFilter"
        :external-filters="processesFilters"
        :refresh-key="refreshKey"
      />

    </KeepAlive>

    <!-- Shared Connector Detail Drawer -->
    <ConnectorDetailDrawer
      v-model="connectorDrawerOpen"
      :connector="selectedConnector"
      :time-filter="activeTimeFilter"
      :show-back-button="connectorFromAudit"
      back-label="Back to Process Audit"
      @back="handleConnectorBackToAudit"
      @hidden="onConnectorDrawerHidden"
    />

    <!-- Processes Drawer (opened from heatmap click) -->
    <ProcessesDrawer
      v-model="processesDrawerOpen"
      :topology-id="processesDrawerTopology"
      :time-range="processesDrawerTimeRange"
      @open-audit="handleOpenAudit"
      @hidden="onProcessesDrawerHidden"
    />

    <!-- Process Audit Drawer (opened from processes drawer magnifier) -->
    <ProcessAuditDrawer
      v-model="auditDrawerOpen"
      :process="auditDrawerProcess"
      :show-back-button="true"
      drawer-id="dashboard-audit-drawer"
      @back="handleAuditBack"
      @hidden="onAuditDrawerHidden"
      @open-connector-detail="handleAuditOpenConnector"
    />

  </div></main>
</template>
