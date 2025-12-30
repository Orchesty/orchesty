<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import SearchInput from '@/components/ui/datagrid/SearchInput.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import DateTimeRangeFilter from '@/components/ui/datagrid/DateTimeRangeFilter.vue'
import ConnectorDetailDrawer from '@/components/dashboard/ConnectorDetailDrawer.vue'
import type { Connector, ConnectorStatus } from '@/types/connectors'
import type { TableColumn, TimeFilter } from '@/types/dashboard'
import type { ActionConfig, QuickFilterOption, DropdownFilterOption } from '@/types/datagrid'
import { fetchConnectors } from '@/services/connectorsService'
import { fetchApplicationNames } from '@/services/applicationsService'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApi } from '@/utils/timeRangeConverter'
import { useDataGrid } from '@/composables/useDataGrid'

interface Props {
  globalTimeFilter: TimeFilter
}

const props = defineProps<Props>()

const connectors = ref<Connector[]>([])
const quickFilter = ref<ConnectorStatus>('all')
const searchQuery = ref('')
const selectedApp = ref<string | null>(null)

// Local datetime range filters
const dateTimeRange = ref<{ from: string | null; to: string | null }>({
  from: null,
  to: null,
})

// Drawer state
const drawerOpen = ref(false)
const selectedConnector = ref<Connector | null>(null)

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
  { value: 'ok', label: 'OK' },
  { value: 'errors', label: 'Errors' },
]

// Dropdown filter options (loaded dynamically)
const applicationOptions = ref<DropdownFilterOption[]>([
  { value: null, label: 'All Applications' },
])

// Actions configuration
const actions: ActionConfig[] = [
  {
    icon: 'search',
    title: 'View details',
    onClick: (row) => {
      selectedConnector.value = row as Connector
      drawerOpen.value = true
    },
  },
]

// Load data function (will be passed to useDataGrid)
const loadData = async () => {
  loading.value = true

  try {
    const response = await fetchConnectors({
      status: quickFilter.value,
      search: searchQuery.value || undefined,
      application: selectedApp.value || undefined,
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
  defaultSort: { field: 'name', direction: 'asc' },
  onDataLoad: loadData,
  filters: [quickFilter, searchQuery, selectedApp, dateTimeRange],
})

// Watch global time filter and convert to local datetime range
watch(
  () => props.globalTimeFilter,
  (newFilter) => {
    const range = convertTimeFilterToDateTimeRange(newFilter)
    dateTimeRange.value = {
      from: range.from,
      to: range.to,
    }
  },
  { immediate: true }
)

onMounted(async () => {
  // Load applications for dropdown filter
  try {
    const appNames = await fetchApplicationNames()
    applicationOptions.value = [
      { value: null, label: 'All Applications' },
      ...appNames.map((name) => ({ value: name, label: name })),
    ]
  } catch (error) {
    console.error('Failed to load applications:', error)
  }

  // Load initial data
  loadData()
})
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
      <!-- Search Input -->
      <SearchInput
        v-model="searchQuery"
        placeholder="Search for connector or application"
      />

      <!-- Application Dropdown -->
      <DropdownFilter v-model="selectedApp" :options="applicationOptions" />

      <!-- DateTime Range Filter -->
      <DateTimeRangeFilter v-model="dateTimeRange" />
    </template>

    <!-- Custom Cells -->
    <template #cell-application="{ value }">
      <span class="font-medium text-gray-900 dark:text-white">{{ value }}</span>
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
      <button
        v-if="value > 0"
        class="inline-flex cursor-pointer items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-800 dark:text-yellow-300 dark:hover:bg-yellow-700"
      >
        {{ value }}
      </button>
      <span v-else class="text-gray-400">-</span>
    </template>

    <template #cell-errors500="{ value }">
      <button
        v-if="value > 0"
        class="inline-flex cursor-pointer items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-200 dark:bg-red-800 dark:text-red-300 dark:hover:bg-red-700"
      >
        {{ value }}
      </button>
      <span v-else class="text-gray-400">-</span>
    </template>

    <template #cell-lastRequestStatus="{ value }">
      <span
        :class="[
          'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
          value === 200
            ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-300'
            : value >= 400 && value < 500
            ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-300'
            : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-300',
        ]"
      >
        {{ value }}
      </span>
      </template>
    </DataGrid>
    </div>
  </Card>

  <!-- Connector Detail Drawer -->
  <ConnectorDetailDrawer
    v-model="drawerOpen"
    :connector="selectedConnector"
    :global-time-filter="props.globalTimeFilter"
  />
</template>

