<script setup lang="ts">
import { computed } from 'vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { LogEntry, LogSeverity } from '@/types/logs'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

const { formatDateTime } = useDateFormat()
const { getTopologyName, getNodeName } = useTopologyNodeMappings()

interface Props {
  log: LogEntry
}

const props = defineProps<Props>()

const formattedTimestamp = computed(() => formatDateTime(props.log.timestamp))

const severityVariant: Record<LogSeverity, 'red' | 'yellow' | 'blue' | 'gray'> = {
  error: 'red',
  warning: 'yellow',
  info: 'blue',
  debug: 'gray',
}

const formatContext = (context: Record<string, unknown>): { label: string; value: string }[] => {
  return Object.entries(context).map(([key, value]) => ({
    label: key.replace(/([A-Z])/g, ' $1').replace(/^./, (str) => str.toUpperCase()),
    value: String(value),
  }))
}
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 gap-4">
      <div>
        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
          Timestamp
        </label>
        <p class="text-sm text-gray-900 dark:text-white">{{ formattedTimestamp }}</p>
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
      <div>
        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
          Severity
        </label>
        <StatusBadge :variant="severityVariant[log.severity] || 'blue'">
          {{ log.severity.charAt(0).toUpperCase() + log.severity.slice(1) }}
        </StatusBadge>
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
</template>
