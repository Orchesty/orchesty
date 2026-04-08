<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { RouterLink } from 'vue-router'
import DataGrid from '@/components/ui/DataGrid.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import FailedMessageModal from '@/components/topologies/FailedMessageModal.vue'
import ConnectorMetricDetailModal from '@/components/dashboard/ConnectorMetricDetailModal.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import GridLink from '@/components/ui/datagrid/GridLink.vue'
import type { ConnectorErrorRecord } from '@/types/connectors'
import type { ProcessTrashItem } from '@/types/processes'
import type { TableColumn } from '@/types/dashboard'
import type { ActionConfig, QuickFilterOption } from '@/types/datagrid'
import type { TimeFilter as TimeFilterType } from '@/types/dashboard'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import {
  fetchProcessAuditErrorRecords,
  fetchProcessAuditTrash,
  fetchConnectorTrash,
} from '@/services/processesService'
import {
  fetchConnectorErrorRecords,
  type ConnectorErrorRecordsCodeFilter,
} from '@/services/connectorsService'

const props = defineProps<{
  /** 'process' = filter by correlation id; 'connector' = filter by node id(s) + time */
  filterMode: 'process' | 'connector'
  correlationId?: string
  topologyId?: string
  nodeIds?: string[]
  timeFilter?: TimeFilterType
}>()

const { getTopologyName, getNodeName, getApplicationNameByNodeId } = useTopologyNodeMappings()

const activeTab = ref<'errorRecords' | 'failedMessages'>('errorRecords')

const errorRecords = ref<ConnectorErrorRecord[]>([])
const errorRecordsLoading = ref(false)
const errorRecordsPage = ref(1)
const errorRecordsTotalPages = ref(1)
const errorRecordsTotalItems = ref(0)
const errorRecordsPerPage = ref(10)
const errorRecordsSortField = ref('created')
const errorRecordsSortDirection = ref<'asc' | 'desc'>('desc')

const codeQuickFilter = ref<ConnectorErrorRecordsCodeFilter>('all')
const codeQuickFilterOptions: QuickFilterOption[] = [
  { value: 'all', label: 'All' },
  { value: '400', label: '400' },
  { value: '500', label: '500' },
]

const trashItems = ref<ProcessTrashItem[]>([])
const trashTotal = ref(0)
const trashError = ref(false)

const errorRecordsColumnsProcess: TableColumn[] = [
  { key: 'connector', label: 'Connector' },
  { key: 'code', label: 'Code' },
  { key: 'message', label: 'Error Message' },
]

const errorRecordsColumnsConnector: TableColumn[] = [
  { key: 'timestamp', label: 'Timestamp', sortable: true, className: 'w-48' },
  { key: 'topology', label: 'Topology' },
  { key: 'code', label: 'Code' },
  { key: 'message', label: 'Error Message' },
]

const errorRecordsColumns = computed(() =>
  props.filterMode === 'connector' ? errorRecordsColumnsConnector : errorRecordsColumnsProcess,
)

const metricDetailOpen = ref(false)
const selectedErrorRecord = ref<ConnectorErrorRecord | null>(null)

const openMetricDetail = (record: ConnectorErrorRecord) => {
  selectedErrorRecord.value = record
  metricDetailOpen.value = true
}

const errorRecordActions: ActionConfig[] = [
  {
    icon: 'search',
    title: 'View detail',
    onClick: (row) => openMetricDetail(row as ConnectorErrorRecord),
  },
]

// --- Load error records ---
const loadErrorRecords = async () => {
  if (props.filterMode === 'process' && !props.correlationId) return
  if (props.filterMode === 'connector' && (!props.nodeIds?.length || !props.timeFilter)) return

  errorRecordsLoading.value = true
  try {
    if (props.filterMode === 'process' && props.correlationId) {
      const apiSort = errorRecordsSortField.value
      const result = await fetchProcessAuditErrorRecords(
        props.correlationId,
        errorRecordsPage.value,
        errorRecordsPerPage.value,
        apiSort,
        errorRecordsSortDirection.value,
        codeQuickFilter.value,
      )
      errorRecords.value = result.data
      errorRecordsTotalPages.value = result.meta.totalPages
      errorRecordsTotalItems.value = result.meta.totalItems
    } else if (props.filterMode === 'connector' && props.nodeIds?.length && props.timeFilter) {
      const apiSortField =
        errorRecordsSortField.value === 'timestamp' ? 'created' : errorRecordsSortField.value
      const result = await fetchConnectorErrorRecords(
        props.nodeIds,
        props.timeFilter,
        errorRecordsPage.value,
        errorRecordsPerPage.value,
        apiSortField,
        errorRecordsSortDirection.value,
        codeQuickFilter.value,
      )
      errorRecords.value = result.data
      errorRecordsTotalPages.value = result.meta.totalPages
      errorRecordsTotalItems.value = result.meta.totalItems
    }
  } catch (e) {
    console.error('Error loading error records:', e)
  } finally {
    errorRecordsLoading.value = false
  }
}

const loadTrash = async () => {
  if (props.filterMode === 'process' && !props.correlationId) return
  if (props.filterMode === 'connector' && (!props.nodeIds?.length || !props.timeFilter)) return

  trashError.value = false
  try {
    if (props.filterMode === 'process' && props.correlationId) {
      const result = await fetchProcessAuditTrash(props.correlationId)
      trashItems.value = result.items
      trashTotal.value = result.total
    } else if (props.filterMode === 'connector' && props.nodeIds?.length && props.timeFilter) {
      const result = await fetchConnectorTrash(props.nodeIds, props.timeFilter)
      trashItems.value = result.items
      trashTotal.value = result.total
    }
  } catch (e) {
    console.error('Error loading trash:', e)
    trashError.value = true
  }
}

const reloadAll = async () => {
  activeTab.value = 'errorRecords'
  errorRecordsPage.value = 1
  errorRecordsSortField.value = props.filterMode === 'connector' ? 'timestamp' : 'created'
  errorRecordsSortDirection.value = 'desc'
  codeQuickFilter.value = 'all'
  await Promise.all([loadErrorRecords(), loadTrash()])
}

watch(
  () =>
    [
      props.filterMode,
      props.correlationId ?? '',
      props.nodeIds?.join(',') ?? '',
      props.timeFilter,
    ] as const,
  () => {
    void reloadAll()
  },
  { immediate: true, deep: true },
)

const handleErrorRecordsPageChange = (page: number) => {
  errorRecordsPage.value = page
  void loadErrorRecords()
}

const handleErrorRecordsPerPageChange = (perPage: number) => {
  errorRecordsPerPage.value = perPage
  errorRecordsPage.value = 1
  void loadErrorRecords()
}

const handleErrorRecordsSort = (config: { field: string; direction: 'asc' | 'desc' }) => {
  errorRecordsSortField.value = config.field
  errorRecordsSortDirection.value = config.direction
  errorRecordsPage.value = 1
  void loadErrorRecords()
}

const onCodeQuickFilterChange = (value: string) => {
  codeQuickFilter.value = value as ConnectorErrorRecordsCodeFilter
  errorRecordsPage.value = 1
  void loadErrorRecords()
}

// Failed message modal
const failedModalOpen = ref(false)
const failedModalNodeId = ref('')
const failedModalTopologyId = ref('')
const failedModalCorrelationId = ref('')

const handleOpenFailedMessage = (item: ProcessTrashItem) => {
  failedModalNodeId.value = item.whereItFailed
  failedModalTopologyId.value = item.topologyId ?? props.topologyId ?? ''
  failedModalCorrelationId.value =
    item.correlationId ?? (props.filterMode === 'process' ? props.correlationId ?? '' : '')
  failedModalOpen.value = true
}

const trashLinkQuery = computed(() => {
  if (props.filterMode === 'process' && props.correlationId) {
    return { correlationId: props.correlationId }
  }
  if (props.filterMode === 'connector' && props.nodeIds?.length && props.timeFilter) {
    return {
      node: props.nodeIds.join(','),
      timeRange: props.timeFilter,
    }
  }
  return {}
})
</script>

<template>
  <div>
    <div class="border-b border-gray-200 dark:border-gray-700">
      <ul class="-mb-px flex text-sm font-medium">
        <li>
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-2 border-b-2 px-4 py-3',
              activeTab === 'errorRecords'
                ? 'border-primary-600 text-primary-600 dark:border-primary-500 dark:text-primary-500'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-600 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300',
            ]"
            @click="activeTab = 'errorRecords'"
          >
            Error Records
          </button>
        </li>
        <li>
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-2 border-b-2 px-4 py-3',
              activeTab === 'failedMessages'
                ? 'border-primary-600 text-primary-600 dark:border-primary-500 dark:text-primary-500'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-600 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300',
            ]"
            @click="activeTab = 'failedMessages'"
          >
            Failed Messages
            <span
              v-if="trashTotal > 0"
              class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-800 dark:text-red-300"
            >
              {{ trashTotal }}
            </span>
          </button>
        </li>
      </ul>
    </div>

    <div v-if="activeTab === 'errorRecords'" class="mt-4">
      <DataGrid
        :columns="errorRecordsColumns"
        :data="errorRecords"
        :loading="errorRecordsLoading"
        :current-page="errorRecordsPage"
        :total-pages="errorRecordsTotalPages"
        :total-items="errorRecordsTotalItems"
        :items-per-page="errorRecordsPerPage"
        :sort-field="errorRecordsSortField"
        :sort-direction="errorRecordsSortDirection"
        :actions="errorRecordActions"
        @page-change="handleErrorRecordsPageChange"
        @per-page-change="handleErrorRecordsPerPageChange"
        @sort="handleErrorRecordsSort"
      >
        <template #quick-filters>
          <QuickFilter
            :model-value="codeQuickFilter"
            name="audit-error-records-code"
            label="Show only:"
            :options="codeQuickFilterOptions"
            @update:model-value="onCodeQuickFilterChange"
          />
        </template>

        <template v-if="filterMode === 'process'" #cell-connector="{ row }">
          <span class="font-medium text-gray-900 dark:text-white">{{ getNodeName(row.nodeId) }}</span>
          <span class="ml-1 text-gray-500 dark:text-gray-400">({{ getApplicationNameByNodeId(row.nodeId) }})</span>
        </template>

        <template v-if="filterMode === 'connector'" #cell-timestamp="{ value }">
          <span class="whitespace-nowrap font-medium text-gray-900 dark:text-white">
            {{ value }}
          </span>
        </template>

        <template v-if="filterMode === 'connector'" #cell-topology="{ row }">
          <GridLink :to="{ name: 'topology-detail', params: { id: row.topologyId } }">
            {{ getTopologyName(row.topologyId) }}
          </GridLink>
        </template>

        <template #cell-code="{ value }">
          <StatusBadge :variant="value >= 400 && value < 500 ? 'yellow' : 'red'">
            {{ value }}
          </StatusBadge>
        </template>

        <template #cell-message="{ value }">
          <span class="break-words text-xs">
            {{ value }}
          </span>
        </template>
      </DataGrid>
    </div>

    <div v-if="activeTab === 'failedMessages'" class="mt-4">
      <div v-if="trashError">
        <p class="text-sm text-yellow-600 dark:text-yellow-400">
          Failed to load trash data. The server may be temporarily unavailable.
        </p>
      </div>
      <div v-else-if="trashTotal > 0">
        <div class="mb-4">
          <RouterLink
            :to="{ name: 'trash', query: trashLinkQuery }"
            class="flex items-center text-sm font-medium text-gray-700 hover:underline dark:text-gray-300"
          >
            Go to Failed Messages
            <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
          </RouterLink>
        </div>

        <div class="overflow-hidden">
          <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
              <tr>
                <th scope="col" class="px-4 py-3">Where it failed</th>
                <th scope="col" class="px-4 py-3">Error message</th>
                <th scope="col" class="w-12 px-4 py-3"><span class="sr-only">Actions</span></th>
              </tr>
            </thead>
            <tbody class="divide-y bg-white dark:divide-gray-700 dark:bg-gray-800">
              <tr v-for="(item, index) in trashItems" :key="index">
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                  {{ getNodeName(item.whereItFailed) }}
                </td>
                <td class="px-4 py-3 break-words">{{ item.errorMessage }}</td>
                <td class="px-4 py-3">
                  <button
                    type="button"
                    class="inline-flex items-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                    title="View detail"
                    @click="handleOpenFailedMessage(item)"
                  >
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <p v-else class="text-sm text-gray-500 dark:text-gray-400">No failed messages</p>
    </div>
  </div>

  <FailedMessageModal
    v-if="failedModalTopologyId && failedModalCorrelationId"
    v-model="failedModalOpen"
    :topology-id="failedModalTopologyId"
    :node-id="failedModalNodeId"
    :correlation-id="failedModalCorrelationId"
    node-name=""
    hide-bulk-actions
  />

  <ConnectorMetricDetailModal v-model="metricDetailOpen" :record="selectedErrorRecord" />
</template>
