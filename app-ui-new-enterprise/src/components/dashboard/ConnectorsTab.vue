<script setup lang="ts">
import { ref, computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import SearchableDropdownFilter from '@/components/ui/datagrid/SearchableDropdownFilter.vue'
import DateTimeRangeFilter from '@/components/ui/datagrid/DateTimeRangeFilter.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { Connector, ConnectorStatus } from '@/types/connectors'
import type { TableColumn, TimeFilter } from '@/types/dashboard'
import type { ActionConfig, QuickFilterOption } from '@/types/datagrid'
import { fetchConnectors } from '@/services/connectorsService'
import { formatDateTimeForApi } from '@/utils/timeRangeConverter'
import { useDataGrid } from '@/composables/useDataGrid'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useDashboardTimeSync } from '@/composables/useDashboardTimeSync'

interface Props {
  timeFilter: TimeFilter
  refreshKey?: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  openConnectorDetail: [connector: Connector]
}>()

// Use topology/node/application mappings composable
const {
  getApplicationName,
  getNodeName,
  applicationOptions: applicationOptionsFromMappings,
} = useTopologyNodeMappings()

function formatName(raw: string): string {
  return raw ? raw.replace(/[-_]/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : ''
}

function formatApplicationName(appKey: string): string {
  if (!appKey) return ''
  const resolved = getApplicationName(appKey)
  return resolved !== appKey ? resolved : formatName(appKey)
}

function formatNodeName(connector: Connector): string {
  if (connector.nodeIds.length > 0) {
    const resolved = getNodeName(connector.nodeIds[0]!)
    if (resolved !== connector.nodeIds[0]) return resolved
  }
  return formatName(connector.name)
}
const { dateTimeRange, markFresh, connectLoadData } = useDashboardTimeSync({
  timeFilter: () => props.timeFilter,
  refreshKey: () => props.refreshKey,
})

const connectors = ref<Connector[]>([])
const quickFilter = ref<ConnectorStatus>('all')
const selectedApp = ref<string | null>(null)

// Table columns (actions column added automatically by DataGrid)
const columns: TableColumn[] = [
  { key: 'application', label: 'Application', sortable: true },
  { key: 'name', label: 'Connector', sortable: true },
  { key: 'avgRequestTime', label: 'Avg request time', sortable: true },
  { key: 'requests', label: 'Requests', sortable: true },
  { key: 'errors400', label: 'Status 400', sortable: true },
  { key: 'errors500', label: 'Status 500', sortable: true },
  { key: 'lastRequestStatus', label: 'Last request status', sortable: true },
]

// Quick filter options
const quickFilterOptions: QuickFilterOption[] = [
  { value: 'all', label: 'All' },
  { value: 'with-activity', label: 'With activity' },
  { value: 'with-errors', label: 'With errors' },
]

// Dropdown filter options (loaded from mappings)
const applicationOptions = computed(() => {
  return [
    { value: null, label: 'All Applications' },
    ...applicationOptionsFromMappings.value
  ]
})

// Actions configuration
const actions: ActionConfig[] = [
  {
    icon: 'search',
    title: 'View details',
    onClick: (row) => {
      emit('openConnectorDetail', row as Connector)
    },
  },
]

// Load data function (will be passed to useDataGrid)
const loadData = async () => {
  loading.value = true

  try {
    const response = await fetchConnectors({
      status: quickFilter.value,
      application: selectedApp.value?.includes(':')
        ? selectedApp.value.substring(selectedApp.value.indexOf(':') + 1)
        : selectedApp.value || undefined,
      dateFrom: formatDateTimeForApi(dateTimeRange.value.from) || undefined,
      dateTo: formatDateTimeForApi(dateTimeRange.value.to) || undefined,
      page: currentPage.value,
      limit: itemsPerPage.value,
      sort: sortField.value,
      order: sortDirection.value,
    })

    connectors.value = response.data

    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems
    markFresh()
  } catch (error) {
    console.error('Error loading connectors:', error)
  } finally {
    loading.value = false
  }
}

// Use DataGrid composable
const {
  currentPage,
  itemsPerPage,
  totalPages,
  totalItems,
  sortField,
  sortDirection,
  loading,
  handlePageChange,
  handlePerPageChange,
  handleSort,
} = useDataGrid({
  defaultSort: { field: 'requests', direction: 'desc' },
  onDataLoad: loadData,
  filters: [quickFilter, selectedApp, dateTimeRange],
})

connectLoadData(loadData)

</script>

<template>
  <Card>
    <div class="mb-3">
      <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Connectors</h3>

      <DataGrid
      :columns="columns"
      :data="connectors"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      :total-items="totalItems"
      :items-per-page="itemsPerPage"
      :sort-field="sortField"
      :sort-direction="sortDirection"
      :actions="actions"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
      @sort="handleSort"
    >
    <!-- Quick Filters (left) -->
    <template #quick-filters>
      <QuickFilter
        v-model="quickFilter"
        name="connectors-filter"
        label="Show only:"
        :options="quickFilterOptions"
      />
    </template>

    <!-- Regular Filters (right) -->
    <template #filters>
      <SearchableDropdownFilter v-model="selectedApp" :options="applicationOptions" placeholder="All Applications" />

      <!-- DateTime Range Filter -->
      <DateTimeRangeFilter v-model="dateTimeRange" />
    </template>

    <!-- Custom Cells -->
    <template #cell-application="{ row }">
      <span class="font-medium text-gray-900 dark:text-white">{{ formatApplicationName((row as Connector).application) }}</span>
    </template>

    <template #cell-name="{ row }">
      <span class="text-gray-900 dark:text-white">{{ formatNodeName((row as Connector)) }}</span>
    </template>

    <template #cell-avgRequestTime="{ value }">
      <div v-if="value > 0" class="flex items-center space-x-2">
        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ value }}ms</span>
        <div class="h-2 w-24 rounded-full bg-gray-200 dark:bg-gray-700">
          <div
            class="h-2 rounded-full bg-primary-500"
            :style="{ width: Math.min(100, (value / 400) * 100) + '%' }"
          ></div>
        </div>
      </div>
      <span v-else class="text-gray-400">-</span>
    </template>

    <template #cell-requests="{ value }">
      <span v-if="value > 0" class="text-sm font-medium text-gray-900 dark:text-white">{{ value.toLocaleString() }}</span>
      <span v-else class="text-gray-400">-</span>
    </template>

    <template #cell-errors400="{ value }">
      <StatusBadge v-if="value > 0" variant="yellow" class="cursor-pointer hover:bg-yellow-200 dark:hover:bg-yellow-700">
        {{ value }}
      </StatusBadge>
      <span v-else class="text-gray-400">-</span>
    </template>

    <template #cell-errors500="{ value }">
      <StatusBadge v-if="value > 0" variant="red" class="cursor-pointer hover:bg-red-200 dark:hover:bg-red-700">
        {{ value }}
      </StatusBadge>
      <span v-else class="text-gray-400">-</span>
    </template>

    <template #cell-lastRequestStatus="{ value }">
      <StatusBadge v-if="value > 0" :variant="value >= 200 && value < 300 ? 'green' : value >= 400 && value < 500 ? 'yellow' : 'red'">
        {{ value }}
      </StatusBadge>
      <span v-else class="text-gray-400">-</span>
      </template>
    </DataGrid>
    </div>
  </Card>

</template>

