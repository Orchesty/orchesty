<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import TimeRangeFilter from '@/components/ui/TimeRangeFilter.vue'
import type { Topology, TopologyStatus } from '@/types/topologies'
import type { TableColumn } from '@/types/dashboard'
import type { QuickFilterOption } from '@/types/datagrid'
import { fetchTopologies } from '@/services/topologiesService'

const loading = ref(true)
const topologies = ref<Topology[]>([])
const quickFilter = ref<TopologyStatus>('all')
const timeRange = ref('this-month')
const currentPage = ref(1)
const itemsPerPage = ref(10)
const totalPages = ref(1)
const totalItems = ref(0)

// Sorting
const sortField = ref('name')
const sortDirection = ref<'asc' | 'desc'>('asc')

// Table columns
const columns: TableColumn[] = [
  { key: 'name', label: 'Topologies', sortable: true },
  { key: 'processesRun', label: 'Processes run', sortable: true },
  { key: 'failedProcesses', label: 'Failed processes', sortable: true },
  { key: 'lastRunTime', label: 'Last run time', sortable: true },
  { key: 'lastRunStatus', label: 'Last run', sortable: true },
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
      timeRange: timeRange.value,
      page: currentPage.value,
      limit: itemsPerPage.value,
      sort: sortField.value,
      order: sortDirection.value,
    })

    topologies.value = response.data
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems
  } catch (error) {
    console.error('Error loading topologies:', error)
  } finally {
    loading.value = false
  }
}

const handlePageChange = (page: number) => {
  currentPage.value = page
}

const handlePerPageChange = (perPage: number) => {
  itemsPerPage.value = perPage
  currentPage.value = 1
}

const handleSort = (config: { field: string; direction: 'asc' | 'desc' }) => {
  sortField.value = config.field
  sortDirection.value = config.direction
  currentPage.value = 1
}

const handleViewProcesses = (topology: Topology) => {
  // TODO: Navigate to Processes tab and filter by topology
  console.log('View processes for topology:', topology.name)
  // Future implementation:
  // router.push({ name: 'dashboard', hash: '#processes', query: { topology: topology.id } })
}

// Watch for filter changes
watch([quickFilter, timeRange], () => {
  currentPage.value = 1
  loadData()
})

watch(currentPage, () => {
  loadData()
})

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
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
        <!-- Filters -->
        <template #filters>
          <div class="flex items-center justify-between py-2">
            <!-- Quick Filter (left) -->
            <QuickFilter
              v-model="quickFilter"
              name="topologies-filter"
              label="Show only:"
              :options="quickFilterOptions"
            />

            <!-- Time Range Filter (right) -->
            <TimeRangeFilter v-model="timeRange" />
          </div>
        </template>

        <!-- Custom Cells -->
        <template #cell-name="{ value }">
          <span class="whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ value }}</span>
        </template>

        <template #cell-processesRun="{ value }">
          <span class="whitespace-nowrap">{{ value.toLocaleString() }}</span>
        </template>

        <template #cell-failedProcesses="{ value }">
          <span
            v-if="value > 0"
            class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-800 dark:text-red-300"
          >
            {{ value }}
          </span>
          <span v-else class="text-gray-400">-</span>
        </template>

        <template #cell-lastRunTime="{ value }">
          <span class="whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ value }}</span>
        </template>

        <template #cell-lastRunStatus="{ value }">
          <span
            :class="[
              'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
              value === 'success'
                ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-300'
                : value === 'running'
                ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-300'
                : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-300',
            ]"
          >
            {{ value.charAt(0).toUpperCase() + value.slice(1) }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <div class="text-right">
            <button
              type="button"
              title="View processes"
              class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              @click="handleViewProcesses(row as Topology)"
            >
              <svg
                class="-ms-0.5 me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  d="M5.05 3C3.291 3 2.352 5.024 3.51 6.317l5.422 6.059v4.874c0 .472.227.917.613 1.2l3.069 2.25c1.01.742 2.454.036 2.454-1.2v-7.124l5.422-6.059C21.647 5.024 20.708 3 18.95 3H5.05Z"
                />
              </svg>
              Processes
            </button>
          </div>
        </template>
      </DataGrid>
    </div>
  </div>
</template>
