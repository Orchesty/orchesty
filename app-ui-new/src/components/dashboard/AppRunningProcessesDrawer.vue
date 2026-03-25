<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import GridLink from '@/components/ui/datagrid/GridLink.vue'
import type { Process } from '@/types/processes'
import type { TableColumn } from '@/types/dashboard'
import { fetchProcesses } from '@/services/processesService'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

interface Props {
  modelValue: boolean
  applicationId: string | null
  topologyIds: string[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const { getTopologyName, getApplicationName } = useTopologyNodeMappings()
const { formatDateTime, formatDurationMs } = useDateFormat()

const appLabel = computed(() => {
  if (!props.applicationId || props.applicationId === '-') return '-'
  return getApplicationName(props.applicationId)
})

const allProcesses = ref<Process[]>([])
const allLoading = ref(false)
const topologyFilter = ref<string | null>(null)

// --- Grid 1: topology summary — only running processes ---

const summaryColumns: TableColumn[] = [
  { key: 'topology', label: 'Topology', sortable: false },
  { key: 'running', label: 'Running processes', sortable: false },
  { key: 'actions', label: '', className: 'text-right w-16' },
]

const topologySummary = computed(() => {
  const byTopology = new Map<string, number>()
  for (const p of allProcesses.value) {
    byTopology.set(p.topologyId, (byTopology.get(p.topologyId) || 0) + 1)
  }
  return [...byTopology.entries()]
    .map(([topologyId, count]) => ({ topologyId, running: count }))
    .sort((a, b) => b.running - a.running)
})

const topologyFilterOptions = computed(() => [
  { value: null, label: 'All Topologies' },
  ...topologySummary.value.map(t => ({
    value: t.topologyId,
    label: getTopologyName(t.topologyId),
  })),
])

// --- Grid 2: running processes (paginated, filtered by topology) ---

const processColumns: TableColumn[] = [
  { key: 'topology', label: 'Topology', sortable: false },
  { key: 'startTime', label: 'Start time', sortable: true },
  { key: 'duration', label: 'Duration', sortable: true },
]

const processes = ref<Process[]>([])

const loadProcesses = async () => {
  loading.value = true
  try {
    const topoIds = topologyFilter.value
      ? [topologyFilter.value]
      : props.topologyIds

    const response = await fetchProcesses({
      topologyIds: topoIds.length > 0 ? topoIds : undefined,
      status: 'running',
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
  onDataLoad: loadProcesses,
  filters: [topologyFilter],
})

const loadRunning = async () => {
  allLoading.value = true
  try {
    const response = await fetchProcesses({
      topologyIds: props.topologyIds.length > 0 ? props.topologyIds : undefined,
      status: 'running',
      limit: 200,
      page: 1,
      sort: 'startTime',
      order: 'desc',
    })
    allProcesses.value = response.data
  } catch (error) {
    console.error('Error loading running processes:', error)
  } finally {
    allLoading.value = false
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      topologyFilter.value = null
      currentPage.value = 1
      loadRunning()
      loadProcesses()
    }
  },
)

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="app-running-processes-drawer"
    label="Running Processes"
    width="w-1/2 min-w-[600px]"
    @update:model-value="handleClose"
  >
    <!-- Application Header -->
    <div class="mb-6 border-b border-gray-200 pb-6 dark:border-gray-700">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ appLabel }}
      </h2>
    </div>

    <!-- Grid 1: Topologies with running processes -->
    <div class="mb-6">
      <h3 class="mb-3 text-sm font-semibold uppercase text-gray-500 dark:text-gray-400">
        Topologies with running processes
      </h3>

      <DataGrid
        :columns="summaryColumns"
        :data="topologySummary"
        :loading="allLoading"
        :current-page="1"
        :items-per-page="100"
        hide-pagination
      >
        <template #cell-topology="{ row }">
          <GridLink :to="{ name: 'topology-detail', params: { id: row.topologyId } }">
            {{ getTopologyName(row.topologyId) }}
          </GridLink>
        </template>

        <template #cell-running="{ value }">
          <span class="font-medium text-gray-900 dark:text-white">{{ value }}</span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end">
            <router-link
              :to="{ name: 'topology-detail', params: { id: row.topologyId } }"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              title="View topology"
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
              <span class="sr-only">View topology</span>
            </router-link>
          </div>
        </template>
      </DataGrid>
    </div>

    <!-- Grid 2: Running processes -->
    <div>
      <h3 class="mb-3 text-sm font-semibold uppercase text-gray-500 dark:text-gray-400">
        Running processes
      </h3>

      <DataGrid
        :columns="processColumns"
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
        <template #filters>
          <DropdownFilter
            v-model="topologyFilter"
            :options="topologyFilterOptions"
            button-label="Topology"
          />
        </template>

        <template #cell-topology="{ row }">
          <GridLink :to="{ name: 'topology-detail', params: { id: row.topologyId } }">
            {{ getTopologyName(row.topologyId) }}
          </GridLink>
        </template>

        <template #cell-startTime="{ value }">
          <span class="whitespace-nowrap">{{ formatDateTime(value) }}</span>
        </template>

        <template #cell-duration="{ value }">
          <span class="whitespace-nowrap">{{ formatDurationMs(value) }}</span>
        </template>

      </DataGrid>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Close
      </Button>
    </template>
  </Drawer>
</template>
