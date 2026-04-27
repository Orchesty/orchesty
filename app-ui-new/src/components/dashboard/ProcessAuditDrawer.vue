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
import StatusBadge from '@/components/ui/StatusBadge.vue'
import AuditErrorRecordsFailedMessagesTabs from '@/components/dashboard/AuditErrorRecordsFailedMessagesTabs.vue'
import type { Process, ProcessAuditDetail, ProcessConnector } from '@/types/processes'
import type { TableColumn } from '@/types/dashboard'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { fetchProcessAuditConnectors } from '@/services/processesService'
import { useDateFormat } from '@/composables/useDateFormat'

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
  'open-connector-detail': [connector: ProcessConnector]
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

// Connector sort state
const connectorSortField = ref('called')
const connectorSortDirection = ref<'asc' | 'desc'>('desc')

// Connector table columns
const connectorColumns: TableColumn[] = [
  { key: 'connector', label: 'Connector / Application', sortable: false },
  { key: 'called', label: 'Called', sortable: true },
  { key: 'errors400', label: '400', sortable: true },
  { key: 'errors500', label: '500', sortable: true },
  { key: 'actions', label: '', sortable: false, className: 'text-right w-12' },
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
    connectorSortField.value = 'called'
    connectorSortDirection.value = 'desc'

    let connectorsData: ProcessConnector[] = []

    const apiSortField = mapSortFieldToApi(connectorSortField.value)

    try {
      connectorsData = await fetchProcessAuditConnectors(
        newProcess.id,
        apiSortField,
        connectorSortDirection.value,
      )
    } catch (e) {
      console.error('Error loading connectors:', e)
      connectorsError.value = true
    }

    connectors.value = connectorsData
    processDetail.value = {
      processId: newProcess.id,
      topology: newProcess.topology,
      corelId: newProcess.id,
      startTime: newProcess.startTime,
      endTime: calculateEndTime(newProcess.startTime, newProcess.duration).toISOString(),
      status: newProcess.status,
      connectors: connectorsData,
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
          <StatusBadge :variant="processDetail.status === 'completed' ? 'green' : processDetail.status === 'running' ? 'blue' : processDetail.status === 'terminated' ? 'yellow' : 'red'">
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

          <template #cell-actions="{ row }">
            <div class="flex items-center justify-end">
              <button
                type="button"
                title="Connector details"
                class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                @click="emit('open-connector-detail', row as ProcessConnector)"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <span class="sr-only">Connector details</span>
              </button>
            </div>
          </template>
        </DataGrid>
      </div>

      <AuditErrorRecordsFailedMessagesTabs
        v-if="process"
        filter-mode="process"
        :correlation-id="process.id"
        :topology-id="process.topologyId"
      />
    </div>

    <!-- Footer -->
    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Close
      </Button>
    </template>
  </Drawer>
</template>
