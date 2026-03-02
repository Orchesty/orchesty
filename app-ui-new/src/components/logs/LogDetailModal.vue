<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import type { LogEntry, LogSeverity } from '@/types/logs'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

const { formatDateTime } = useDateFormat()
const { getTopologyName, getNodeName } = useTopologyNodeMappings()

interface Props {
  modelValue: boolean
  log: LogEntry | null
}

const props = defineProps<Props>()

defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const formattedTimestamp = computed(() => {
  if (!props.log) return ''
  return formatDateTime(props.log.timestamp)
})

const getSeverityClass = (severity: LogSeverity): string => {
  const classes = {
    error: 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-300',
    warning: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-300',
    info: 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-300',
    debug: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
  }
  return classes[severity] || classes.info
}

const formatContext = (context: Record<string, unknown>): { label: string; value: string }[] => {
  return Object.entries(context).map(([key, value]) => ({
    label: key.replace(/([A-Z])/g, ' $1').replace(/^./, (str) => str.toUpperCase()),
    value: String(value),
  }))
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="log-detail-modal"
    title="Log Detail"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="log" class="space-y-6">
      <div class="space-y-4">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Timestamp
          </label>
          <p class="text-sm text-gray-900 dark:text-white">{{ formattedTimestamp }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Severity
          </label>
          <span
            :class="[
              'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
              getSeverityClass(log.severity),
            ]"
          >
            {{ log.severity.charAt(0).toUpperCase() + log.severity.slice(1) }}
          </span>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Topology
          </label>
          <p class="text-sm text-gray-900 dark:text-white">{{ getTopologyName(log.topologyId) }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Node
          </label>
          <p class="text-sm text-gray-900 dark:text-white">{{ getNodeName(log.nodeId) }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Node ID
          </label>
          <p class="font-mono text-sm text-gray-900 dark:text-white">{{ log.nodeId }}</p>
        </div>
        <div v-if="log.correlationId">
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Correlation ID
          </label>
          <CopyValue :value="log.correlationId">
            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ log.correlationId }}</span>
          </CopyValue>
        </div>
      </div>

      <div>
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Message</h4>
        <div
          class="max-h-64 overflow-y-auto overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
        >
          {{ log.message }}
        </div>
      </div>

      <div v-if="log.additionalContext && Object.keys(log.additionalContext).length > 0">
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
          Additional Context
        </h4>
        <div
          class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900"
        >
          <div class="space-y-2">
            <div
              v-for="item in formatContext(log.additionalContext)"
              :key="item.label"
              class="flex justify-between"
            >
              <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                {{ item.label }}:
              </span>
              <span class="text-xs text-gray-900 dark:text-white">{{ item.value }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Modal>
</template>
