<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import type { ConnectorErrorRecord } from '@/types/connectors'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

const { formatDateTime } = useDateFormat()
const { getTopologyName, getNodeName, getApplicationName } = useTopologyNodeMappings()

interface Props {
  modelValue: boolean
  record: ConnectorErrorRecord | null
}

const props = defineProps<Props>()

defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const formattedTimestamp = computed(() => {
  if (!props.record) return ''
  return formatDateTime(props.record.timestamp)
})

const statusColorClass = computed(() => {
  if (!props.record) return ''
  const code = props.record.code
  if (code >= 200 && code < 300) return 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-300'
  if (code >= 400 && code < 500) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-300'
  return 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-300'
})

const formattedDuration = computed(() => {
  if (!props.record || !props.record.duration) return null
  const ms = props.record.duration
  if (ms < 1000) return `${ms} ms`
  return `${(ms / 1000).toFixed(2)} s`
})
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="connector-metric-detail-modal"
    title="Connector Metric Detail"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="record" class="space-y-6">
      <div class="space-y-4">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Timestamp
          </label>
          <p class="text-sm text-gray-900 dark:text-white">{{ formattedTimestamp }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            HTTP Status
          </label>
          <span
            :class="[
              'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
              statusColorClass,
            ]"
          >
            {{ record.code }}
          </span>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Topology
          </label>
          <p class="text-sm text-gray-900 dark:text-white">{{ getTopologyName(record.topologyId) }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Node
          </label>
          <p class="text-sm text-gray-900 dark:text-white">{{ getNodeName(record.nodeId) }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Application
          </label>
          <p class="text-sm text-gray-900 dark:text-white">{{ getApplicationName(record.applicationId) }}</p>
        </div>
        <div v-if="formattedDuration">
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Duration
          </label>
          <p class="text-sm text-gray-900 dark:text-white">{{ formattedDuration }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Node ID
          </label>
          <CopyValue :value="record.nodeId">
            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ record.nodeId }}</span>
          </CopyValue>
        </div>
        <div v-if="record.correlationId">
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Correlation ID
          </label>
          <CopyValue :value="record.correlationId">
            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ record.correlationId }}</span>
          </CopyValue>
        </div>
        <div v-if="record.userId">
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            User ID
          </label>
          <CopyValue :value="record.userId">
            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ record.userId }}</span>
          </CopyValue>
        </div>
      </div>

      <div v-if="record.message">
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Error Message</h4>
        <div
          class="max-h-64 overflow-y-auto overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
        >
          {{ record.message }}
        </div>
      </div>
    </div>
  </Modal>
</template>
