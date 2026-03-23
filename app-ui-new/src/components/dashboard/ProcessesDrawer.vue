<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import DateTimeRangeFilter from '@/components/ui/datagrid/DateTimeRangeFilter.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { Process, ProcessStatus } from '@/types/processes'
import type { TableColumn } from '@/types/dashboard'
import type { QuickFilterOption } from '@/types/datagrid'
import { fetchProcesses } from '@/services/processesService'
import { formatDateTimeForApi } from '@/utils/timeRangeConverter'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

interface Props {
  modelValue: boolean
  topologyId: string | null
  timeRange: { from: string; to: string } | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'open-audit': [process: Process]
  'hidden': []
}>()

const router = useRouter()
const { getTopologyName, getTopologyNameWithVersion } = useTopologyNodeMappings()
const { formatDateTime, formatDurationMs } = useDateFormat()

const topologyLabel = computed(() => {
  if (!props.topologyId) return ''
  return getTopologyNameWithVersion(props.topologyId)
})

const processes = ref<Process[]>([])
const statusFilter = ref<ProcessStatus>('all')

const dateTimeRange = ref<{ from: string | null; to: string | null }>({
  from: props.timeRange?.from ?? null,
  to: props.timeRange?.to ?? null,
})

const columns: TableColumn[] = [
  { key: 'startTime', label: 'Start time', sortable: true },
  { key: 'duration', label: 'Duration', sortable: true },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'actions', label: '', className: 'text-right' },
]

const quickFilterOptions: QuickFilterOption[] = [
  { value: 'all', label: 'All' },
  { value: 'completed', label: 'Completed' },
  { value: 'running', label: 'Running' },
  { value: 'failed', label: 'Failed' },
]

const loadData = async () => {
  loading.value = true
  try {
    const response = await fetchProcesses({
      status: statusFilter.value,
      topology: props.topologyId || undefined,
      dateFrom: formatDateTimeForApi(dateTimeRange.value.from) || undefined,
      dateTo: formatDateTimeForApi(dateTimeRange.value.to) || undefined,
      page: currentPage.value,
      limit: itemsPerPage.value,
      sort: sortField.value,
      order: sortDirection.value,
    })

    processes.value = response.data
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems
  } catch (error) {
    console.error('Error loading processes:', error)
  } finally {
    loading.value = false
  }
}

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
  defaultSort: { field: 'startTime', direction: 'desc' },
  onDataLoad: loadData,
  filters: [statusFilter, dateTimeRange],
})

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      dateTimeRange.value = {
        from: props.timeRange?.from ?? null,
        to: props.timeRange?.to ?? null,
      }
      statusFilter.value = 'all'
      currentPage.value = 1
      loadData()
    }
  },
)

const handleAuditClick = (process: Process) => {
  emit('open-audit', process)
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="processes-drawer"
    label="Topology Processes"
    width="w-1/2 min-w-[600px]"
    @update:model-value="handleClose"
    @hidden="emit('hidden')"
  >
    <!-- Topology Header -->
    <div class="mb-6 border-b border-gray-200 pb-6 dark:border-gray-700">
      <div class="flex items-start justify-between">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ topologyLabel }}
        </h2>
        <button
          v-if="topologyId"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
          @click="router.push({ name: 'topology-detail', params: { id: topologyId } })"
        >
          Go to Topology
          <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Time Range Filter -->
    <div class="mb-4">
      <DateTimeRangeFilter v-model="dateTimeRange" />
    </div>

    <DataGrid
      :columns="columns"
      :data="processes"
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
      <template #quick-filters>
        <QuickFilter
          v-model="statusFilter"
          name="processes-drawer-filter"
          label="Show only:"
          :options="quickFilterOptions"
        />
      </template>

      <template #cell-startTime="{ value }">
        <span class="whitespace-nowrap">{{ formatDateTime(value) }}</span>
      </template>

      <template #cell-duration="{ value }">
        <span class="whitespace-nowrap">{{ formatDurationMs(value) }}</span>
      </template>

      <template #cell-status="{ value }">
        <StatusBadge :variant="value === 'completed' ? 'green' : value === 'running' ? 'blue' : 'red'">
          {{ value.charAt(0).toUpperCase() + value.slice(1) }}
        </StatusBadge>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center justify-end">
          <button
            type="button"
            title="Audit"
            @click="handleAuditClick(row)"
            class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
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
            <span class="sr-only">Audit</span>
          </button>
        </div>
      </template>
    </DataGrid>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Close
      </Button>
    </template>
  </Drawer>
</template>
