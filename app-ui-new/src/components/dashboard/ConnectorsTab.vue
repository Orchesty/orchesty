<script setup lang="ts">
import { ref, computed, watch } from 'vue'
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
  getApplicationNameByNodeId,
  getNodeName,
  getNodeIdsByName,
  applicationOptions: applicationOptionsFromMappings,
  deduplicatedNodeOptions: deduplicatedNodeOptionsFromMappings,
  mappings,
} = useTopologyNodeMappings()
const { dateTimeRange, markFresh, connectLoadData } = useDashboardTimeSync({
  timeFilter: () => props.timeFilter,
  refreshKey: () => props.refreshKey,
})

const connectors = ref<Connector[]>([])
const quickFilter = ref<ConnectorStatus>('all')
const nodeFilter = ref<string | null>(null)
const selectedApp = ref<string | null>(null)

// Table columns (actions column added automatically by DataGrid)
const columns: TableColumn[] = [
  { key: 'application', label: 'Application', sortable: false },
  { key: 'name', label: 'Connector', sortable: false },
  { key: 'avgRequestTime', label: 'Avg request time', sortable: true },
  { key: 'requests', label: 'Requests', sortable: true },
  { key: 'errors400', label: 'Status 400', sortable: true },
  { key: 'errors500', label: 'Status 500', sortable: true },
  { key: 'lastRequestStatus', label: 'Last request status', sortable: true },
]

// Quick filter options
const quickFilterOptions: QuickFilterOption[] = [
  { value: 'all', label: 'All' },
  { value: 'ok', label: 'OK' },
  { value: 'errors', label: 'Errors' },
]

// Dropdown filter options (loaded from mappings)
const applicationOptions = computed(() => {
  return [
    { value: null, label: 'All Applications' },
    ...applicationOptionsFromMappings.value
  ]
})

// Node options filtered by selected application, deduplicated by name
const nodeOptions = computed(() => {
  const baseOptions = [{ value: null, label: 'All Nodes' }]

  if (!selectedApp.value || !mappings.value) {
    return [...baseOptions, ...deduplicatedNodeOptionsFromMappings.value]
  }

  const nodeIdsForApp = mappings.value.applicationTree?.[selectedApp.value] || []

  const namesForApp = new Set(
    nodeIdsForApp
      .map(id => mappings.value?.nodes[id])
      .filter((name): name is string => !!name)
  )

  const filteredNodes = Array.from(namesForApp)
    .map(name => ({ value: name, label: name }))
    .sort((a, b) => a.label.localeCompare(b.label))

  return [...baseOptions, ...filteredNodes]
})

// Clear node filter when application changes if selected node is not in new application
watch(selectedApp, () => {
  if (nodeFilter.value && selectedApp.value && mappings.value) {
    const nodeIdsForApp = mappings.value.applicationTree?.[selectedApp.value] || []
    const namesForApp = new Set(
      nodeIdsForApp
        .map(id => mappings.value?.nodes[id])
        .filter(Boolean)
    )

    if (!namesForApp.has(nodeFilter.value)) {
      nodeFilter.value = null
    }
  }
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
    const appNodeIds = selectedApp.value && mappings.value?.applicationTree?.[selectedApp.value]
      ? mappings.value.applicationTree[selectedApp.value]
      : undefined
    const nameNodeIds = nodeFilter.value ? getNodeIdsByName(nodeFilter.value) : undefined

    let combinedNodeIds: string[] | undefined
    if (appNodeIds && nameNodeIds) {
      combinedNodeIds = nameNodeIds.filter(id => appNodeIds.includes(id))
    } else {
      combinedNodeIds = appNodeIds || nameNodeIds
    }

    const response = await fetchConnectors({
      status: quickFilter.value,
      node: combinedNodeIds && combinedNodeIds.length > 0 ? combinedNodeIds : undefined,
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
  filters: [quickFilter, nodeFilter, selectedApp, dateTimeRange],
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
      <SearchableDropdownFilter v-model="nodeFilter" :options="nodeOptions" placeholder="All Nodes" />

      <SearchableDropdownFilter v-model="selectedApp" :options="applicationOptions" placeholder="All Applications" />

      <!-- DateTime Range Filter -->
      <DateTimeRangeFilter v-model="dateTimeRange" />
    </template>

    <!-- Custom Cells -->
    <template #cell-application="{ row }">
      <span class="font-medium text-gray-900 dark:text-white">{{ getApplicationNameByNodeId(row.id) }}</span>
    </template>

    <template #cell-name="{ row }">
      <span class="text-gray-900 dark:text-white">{{ getNodeName(row.id) }}</span>
    </template>

    <template #cell-avgRequestTime="{ value }">
      <div class="flex items-center space-x-2">
        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ value }}ms</span>
        <div class="h-2 w-24 rounded-full bg-gray-200 dark:bg-gray-700">
          <div
            class="h-2 rounded-full bg-primary-500"
            :style="{ width: Math.min(100, (value / 400) * 100) + '%' }"
          ></div>
        </div>
      </div>
    </template>

    <template #cell-requests="{ value }">
      <span class="text-sm font-medium text-gray-900 dark:text-white">{{ value.toLocaleString() }}</span>
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
      <StatusBadge :variant="value >= 200 && value < 300 ? 'green' : value >= 400 && value < 500 ? 'yellow' : 'red'">
        {{ value }}
      </StatusBadge>
      </template>
    </DataGrid>
    </div>
  </Card>

</template>

