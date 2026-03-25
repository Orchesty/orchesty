<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import MoreActions from '@/components/ui/MoreActions.vue'
import type { MoreActionsSection } from '@/components/ui/MoreActions.vue'
import FailedMessageModal from '@/components/topologies/FailedMessageModal.vue'
import type { Process, ProcessAuditDetail, ProcessConnector } from '@/types/processes'
import type { TableColumn } from '@/types/dashboard'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { fetchProcessAuditConnectors, fetchProcessAuditTrash } from '@/services/processesService'
import { useDateFormat } from '@/composables/useDateFormat'
import StatusBadge from '@/components/ui/StatusBadge.vue'

interface Props {
  modelValue: boolean
  process: Process | null
  showBackButton?: boolean
  drawerId?: string
}

const props = withDefaults(defineProps<Props>(), {
  showBackButton: false,
  drawerId: 'process-audit-drawer',
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'back': []
  'hidden': []
}>()

const router = useRouter()
const { getTopologyName, getNodeName, getApplicationNameByNodeId } = useTopologyNodeMappings()
const { formatDateTime } = useDateFormat()

// Data state
const processDetail = ref<ProcessAuditDetail | null>(null)
const connectors = ref<ProcessConnector[]>([])
const loading = ref(false)
const connectorsLoading = ref(false)
const connectorsError = ref(false)
const trashError = ref(false)

// Connector sort state
const connectorSortField = ref('called')
const connectorSortDirection = ref<'asc' | 'desc'>('desc')

// Connector table columns
const connectorColumns: TableColumn[] = [
  { key: 'connector', label: 'Connector / Application', sortable: false },
  { key: 'called', label: 'Called', sortable: true },
  { key: 'errors400', label: '400', sortable: true },
  { key: 'errors500', label: '500', sortable: true },
]

// Map UI sort field to API column name
const mapSortFieldToApi = (field: string): string => {
  const mapping: Record<string, string> = {
    called: 'count',
    errors400: 'status400',
    errors500: 'status500',
  }
  return mapping[field] ?? field
}

// Load connectors with current sort
const loadConnectors = async (correlationId: string) => {
  connectorsLoading.value = true
  try {
    const apiSortField = mapSortFieldToApi(connectorSortField.value)
    const connectorsData = await fetchProcessAuditConnectors(
      correlationId,
      apiSortField,
      connectorSortDirection.value
    )
    connectors.value = connectorsData
  } catch (error) {
    console.error('Error loading connectors:', error)
  } finally {
    connectorsLoading.value = false
  }
}

const handleConnectorSort = (config: { field: string; direction: 'asc' | 'desc' }) => {
  connectorSortField.value = config.field
  connectorSortDirection.value = config.direction
  if (props.process) {
    loadConnectors(props.process.id)
  }
}

// Load process audit detail when process changes
watch(
  () => props.process,
  async (newProcess) => {
    if (!newProcess) {
      processDetail.value = null
      return
    }

    loading.value = true
    connectorsError.value = false
    trashError.value = false
    connectorSortField.value = 'called'
    connectorSortDirection.value = 'desc'

    let connectorsData: ProcessConnector[] = []
    let trashTotal = 0
    let trashItems: { whereItFailed: string; errorMessage: string }[] = []

    const apiSortField = mapSortFieldToApi(connectorSortField.value)

    const [connectorsResult, trashResult] = await Promise.allSettled([
      fetchProcessAuditConnectors(newProcess.id, apiSortField, connectorSortDirection.value),
      fetchProcessAuditTrash(newProcess.id)
    ])

    if (connectorsResult.status === 'fulfilled') {
      connectorsData = connectorsResult.value
    } else {
      console.error('Error loading connectors:', connectorsResult.reason)
      connectorsError.value = true
    }

    if (trashResult.status === 'fulfilled') {
      trashTotal = trashResult.value.total
      trashItems = trashResult.value.items
    } else {
      console.error('Error loading trash data:', trashResult.reason)
      trashError.value = true
    }

    connectors.value = connectorsData
    processDetail.value = {
      processId: newProcess.id,
      topology: newProcess.topology,
      corelId: newProcess.id,
      startTime: newProcess.startTime,
      endTime: calculateEndTime(newProcess.startTime, newProcess.duration),
      status: newProcess.status,
      connectors: connectorsData,
      trashCount: trashTotal,
      trashItems: trashItems,
    }

    loading.value = false
  },
  { immediate: true }
)

const calculateEndTime = (startTime: string, durationMs: number): Date => {
  const start = new Date(startTime)
  return new Date(start.getTime() + durationMs)
}

const moreActionsSections = computed<MoreActionsSection[]>(() => {
  const sections: MoreActionsSection[] = [
    {
      items: [
        { type: 'button', label: 'Export PDF', onClick: () => {} },
        { type: 'button', label: 'Get Payload', onClick: () => {} },
      ],
    },
    {
      items: [
        {
          type: 'button',
          label: 'Go to Topology',
          onClick: () => {
            if (props.process?.topologyId) {
              router.push({ name: 'topology-detail', params: { id: props.process.topologyId } })
            }
          },
        },
        {
          type: 'button',
          label: 'Go to Failed Messages',
          onClick: () => {
            if (processDetail.value?.corelId) {
              router.push({ name: 'trash', query: { correlationId: processDetail.value.corelId } })
            }
          },
        },
      ],
    },
  ]
  return sections
})

// Failed message modal state
const failedModalOpen = ref(false)
const failedModalNodeId = ref('')

const handleOpenFailedMessage = (nodeId: string) => {
  failedModalNodeId.value = nodeId
  failedModalOpen.value = true
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Drawer
    :model-value="modelValue"
    :id="drawerId"
    label="Process Audit"
    width="w-1/2 min-w-[600px]"
    @update:model-value="handleClose"
    @hidden="emit('hidden')"
  >
    <LoadingSpinner v-if="loading" message="Loading process details..." />

    <div v-else-if="processDetail" class="space-y-6">
      <!-- Back button -->
      <button
        v-if="showBackButton"
        type="button"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
        @click="emit('back')"
      >
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Processes
      </button>

      <!-- Topology Header -->
      <div class="mb-6 border-b border-gray-200 pb-6 dark:border-gray-700">
        <div class="mb-3 flex items-start justify-between">
          <div>
            <h2 class="mb-3 text-2xl font-bold text-gray-900 dark:text-white">
              {{ getTopologyName(processDetail.topology) }}
            </h2>
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-500 dark:text-gray-400">Corel ID:</span>
              <CopyValue :value="processDetail.corelId">
                <span class="font-mono text-sm text-gray-900 dark:text-white">{{
                  processDetail.corelId
                }}</span>
              </CopyValue>
            </div>
          </div>
          <MoreActions
            :id="`${drawerId}-more-actions`"
            :sections="moreActionsSections"
          />
        </div>
      </div>

      <!-- Process Info -->
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
            >Start</label
          >
          <p class="text-sm text-gray-900 dark:text-white">{{ formatDateTime(processDetail.startTime) }}</p>
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">End</label>
          <p class="text-sm text-gray-900 dark:text-white">{{ formatDateTime(processDetail.endTime) }}</p>
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
            >Status</label
          >
          <StatusBadge :variant="processDetail.status === 'completed' ? 'green' : processDetail.status === 'running' ? 'blue' : 'red'">
            {{ processDetail.status.charAt(0).toUpperCase() + processDetail.status.slice(1) }}
          </StatusBadge>
        </div>
      </div>

      <!-- Connectors Table -->
      <div>
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Connectors</h4>
        <p v-if="connectorsError" class="mb-3 text-sm text-yellow-600 dark:text-yellow-400">
          Failed to load connectors data. The server may be temporarily unavailable.
        </p>
        <DataGrid
          :columns="connectorColumns"
          :data="connectors"
          :loading="connectorsLoading"
          :sort-field="connectorSortField"
          :sort-direction="connectorSortDirection"
          :total-items="connectors.length"
          :total-pages="1"
          :current-page="1"
          :items-per-page="100"
          hide-pagination
          @sort="handleConnectorSort"
        >
          <template #cell-connector="{ row }">
            <span class="font-medium text-gray-900 dark:text-white">{{ getNodeName(row.connector) }}</span>
            <span class="text-gray-500 dark:text-gray-400"> ({{ row.connector && row.application !== 'N/A' ? getApplicationNameByNodeId(row.connector) : 'N/A' }})</span>
          </template>

          <template #cell-errors400="{ value }">
            <StatusBadge v-if="value > 0" variant="yellow">{{ value }}</StatusBadge>
            <span v-else>-</span>
          </template>

          <template #cell-errors500="{ value }">
            <StatusBadge v-if="value > 0" variant="red">{{ value }}</StatusBadge>
            <span v-else>-</span>
          </template>
        </DataGrid>
      </div>

      <!-- Trash Status -->
      <div v-if="trashError">
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Failed Messages</h4>
        <p class="text-sm text-yellow-600 dark:text-yellow-400">
          Failed to load trash data. The server may be temporarily unavailable.
        </p>
      </div>
      <div v-else-if="processDetail.trashCount > 0">
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Failed Messages</h4>
        <div class="flex items-center gap-3">
          <div
            class="inline-flex items-center rounded-sm bg-red-100 px-3 py-1.5 text-sm font-medium text-red-800 dark:bg-red-800 dark:text-red-300"
          >
            {{ processDetail.trashCount }} messages failed
          </div>
          <router-link
            :to="{ name: 'trash', query: { correlationId: processDetail.corelId } }"
            class="flex shrink-0 items-center text-sm font-medium text-primary-700 hover:underline dark:text-primary-500"
          >
            Go to Failed Messages
          </router-link>
        </div>

        <!-- Trash Table -->
        <div class="mt-4">
          <div class="overflow-hidden">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
              <thead
                class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400"
              >
                <tr>
                  <th scope="col" class="px-4 py-3">Where it failed</th>
                  <th scope="col" class="px-4 py-3">Error message</th>
                  <th scope="col" class="w-12 px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
              </thead>
              <tbody class="divide-y bg-white dark:divide-gray-700 dark:bg-gray-800">
                <tr v-for="(item, index) in processDetail.trashItems" :key="index">
                  <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                    {{ getNodeName(item.whereItFailed) }}
                  </td>
                  <td class="px-4 py-3 break-words">{{ item.errorMessage }}</td>
                  <td class="px-4 py-3">
                    <button
                      type="button"
                      class="inline-flex items-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                      title="View detail"
                      @click="handleOpenFailedMessage(item.whereItFailed)"
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
      </div>

      <div v-else>
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Trash Status</h4>
        <p class="text-sm text-gray-500 dark:text-gray-400">No failed messages</p>
      </div>
    </div>

    <!-- Footer -->
    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Close
      </Button>
    </template>
  </Drawer>

  <FailedMessageModal
    v-if="processDetail && process"
    v-model="failedModalOpen"
    :topology-id="process.topologyId"
    :node-id="failedModalNodeId"
    :correlation-id="processDetail.corelId"
    node-name=""
    hide-bulk-actions
  />
</template>

