<script setup lang="ts">
import { ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import DateTimeRangeFilter from '@/components/ui/datagrid/DateTimeRangeFilter.vue'
import type { Topology, TopologyStatus } from '@/types/topologies'
import type { TableColumn, TimeFilter } from '@/types/dashboard'
import type { QuickFilterOption } from '@/types/datagrid'
import { fetchTopologies } from '@/services/topologiesService'
import { formatDateTimeForApi } from '@/utils/timeRangeConverter'
import { useDataGrid } from '@/composables/useDataGrid'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useDashboardTimeSync } from '@/composables/useDashboardTimeSync'
import GridLink from '@/components/ui/datagrid/GridLink.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'

interface Props {
  timeFilter: TimeFilter
  refreshKey?: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  viewProcesses: [topologyId: string]
}>()

// Use topology/node mappings composable
const { getTopologyNameWithVersion } = useTopologyNodeMappings()
const { dateTimeRange, markFresh, connectLoadData } = useDashboardTimeSync({
  timeFilter: () => props.timeFilter,
  refreshKey: () => props.refreshKey,
})

const topologies = ref<Topology[]>([])
const quickFilter = ref<TopologyStatus>('all')

// Table columns
const columns: TableColumn[] = [
  { key: 'name', label: 'Topologies', sortable: false },
  { key: 'processesRun', label: 'Processes run', sortable: true },
  { key: 'failedProcesses', label: 'Failed processes', sortable: true },
  { key: 'lastRunTime', label: 'Last run time', sortable: true },
  { key: 'lastRunStatus', label: 'Last run', sortable: false },
  { key: 'actions', label: '', className: 'text-right' },
]

// Quick filter options
const quickFilterOptions: QuickFilterOption[] = [
  { value: 'all', label: 'All' },
  { value: 'success', label: 'Success' },
  { value: 'running', label: 'Running' },
  { value: 'failed', label: 'Failed' },
]

const loadData = async () => {
  loading.value = true

  try {
    const response = await fetchTopologies({
      status: quickFilter.value,
      dateFrom: formatDateTimeForApi(dateTimeRange.value.from) || undefined,
      dateTo: formatDateTimeForApi(dateTimeRange.value.to) || undefined,
      page: currentPage.value,
      limit: itemsPerPage.value,
      sort: sortField.value,
      order: sortDirection.value,
    })

    topologies.value = response.data

    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems
    markFresh()
  } catch (error) {
    console.error('Error loading topologies:', error)
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
  defaultSort: { field: 'processesRun', direction: 'desc' },
  onDataLoad: loadData,
  filters: [quickFilter, dateTimeRange],
})

const handleViewProcesses = (topology: Topology) => {
  emit('viewProcesses', topology.id)
}

connectLoadData(loadData)

</script>

<template>
  <Card>
    <div class="mb-3">
      <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Topologies</h3>

      <DataGrid
        :columns="columns"
        :data="topologies"
        :loading="loading"
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalItems"
        :items-per-page="itemsPerPage"
        :sort-field="sortField"
        :sort-direction="sortDirection"
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @sort="handleSort"
      >
        <!-- Quick Filters (left) -->
        <template #quick-filters>
          <QuickFilter
            v-model="quickFilter"
            name="topologies-filter"
            label="Show only:"
            :options="quickFilterOptions"
          />
        </template>

        <!-- Regular Filters (right) -->
        <template #filters>
          <DateTimeRangeFilter v-model="dateTimeRange" />
        </template>

        <!-- Custom Cells -->
        <template #cell-name="{ row }">
          <GridLink :to="{ name: 'topology-detail', params: { id: (row as Topology).id } }">
            {{ getTopologyNameWithVersion((row as Topology).id) }}
          </GridLink>
        </template>

        <template #cell-processesRun="{ value }">
          <span class="whitespace-nowrap">{{ value.toLocaleString() }}</span>
        </template>

        <template #cell-failedProcesses="{ value }">
          <StatusBadge v-if="value > 0" variant="red">
            {{ value }}
          </StatusBadge>
          <span v-else class="text-gray-400">-</span>
        </template>

        <template #cell-lastRunTime="{ value }">
          <span class="whitespace-nowrap">{{ value }}</span>
        </template>

        <template #cell-lastRunStatus="{ value }">
          <StatusBadge :variant="value === 'success' ? 'green' : value === 'running' ? 'blue' : 'red'">
            {{ value.charAt(0).toUpperCase() + value.slice(1) }}
          </StatusBadge>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1">
            <RouterLink
              :to="`/topologies/${(row as Topology).id}`"
              title="View detail"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg
                class="h-5 w-5"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                />
              </svg>
              <span class="sr-only">View detail</span>
            </RouterLink>
            <button
              type="button"
              title="View processes"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              @click="handleViewProcesses(row as Topology)"
            >
              <svg
                class="h-5 w-5"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  d="M5.05 3C3.291 3 2.352 5.024 3.51 6.317l5.422 6.059v4.874c0 .472.227.917.613 1.2l3.069 2.25c1.01.742 2.454.036 2.454-1.2v-7.124l5.422-6.059C21.647 5.024 20.708 3 18.95 3H5.05Z"
                />
              </svg>
              <span class="sr-only">View processes</span>
            </button>
          </div>
        </template>
      </DataGrid>
    </div>
  </Card>
</template>
