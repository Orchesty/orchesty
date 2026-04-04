<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import type { AuditLogEntry } from '@/types/audit-logs'
import { useDateFormat } from '@/composables/useDateFormat'

const { formatDateTime } = useDateFormat()

interface Props {
  modelValue: boolean
  log: AuditLogEntry | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'export'): void
}>()

const formattedBody = computed(() => {
  if (!props.log?.requestBody) return null
  try {
    return JSON.stringify(props.log.requestBody, null, 2)
  } catch {
    return null
  }
})

const handleExport = () => {
  emit('export')
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="audit-log-detail-modal"
    title="Log Detail"
    size="lg"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div v-if="log" class="space-y-3">
      <div class="flex flex-col">
        <dt class="mb-1 text-sm text-gray-500 dark:text-gray-400">Timestamp</dt>
        <dd class="text-sm text-gray-900 dark:text-white">{{ formatDateTime(log.timestamp) }}</dd>
      </div>
      <div class="flex flex-col">
        <dt class="mb-1 text-sm text-gray-500 dark:text-gray-400">User</dt>
        <dd class="text-sm text-gray-900 dark:text-white">{{ log.user }}</dd>
      </div>
      <div class="flex flex-col">
        <dt class="mb-1 text-sm text-gray-500 dark:text-gray-400">Object</dt>
        <dd class="text-sm text-gray-900 dark:text-white">{{ log.object }}</dd>
      </div>
      <div class="flex flex-col">
        <dt class="mb-1 text-sm text-gray-500 dark:text-gray-400">Action</dt>
        <dd class="text-sm text-gray-900 dark:text-white">{{ log.action }}</dd>
      </div>
      <div class="flex flex-col">
        <dt class="mb-1 text-sm text-gray-500 dark:text-gray-400">Request</dt>
        <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ log.note }}</dd>
      </div>

      <div v-if="log.ip || log.userAgent" class="flex gap-6">
        <div v-if="log.ip" class="flex flex-col">
          <dt class="mb-1 text-sm text-gray-500 dark:text-gray-400">IP Address</dt>
          <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ log.ip }}</dd>
        </div>
        <div v-if="log.statusCode" class="flex flex-col">
          <dt class="mb-1 text-sm text-gray-500 dark:text-gray-400">Status</dt>
          <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ log.statusCode }}</dd>
        </div>
      </div>

      <div v-if="log.userAgent" class="flex flex-col">
        <dt class="mb-1 text-sm text-gray-500 dark:text-gray-400">User Agent</dt>
        <dd class="text-xs text-gray-600 dark:text-gray-400 break-all">{{ log.userAgent }}</dd>
      </div>

      <div v-if="formattedBody" class="flex flex-col">
        <dt class="mb-1 text-sm text-gray-500 dark:text-gray-400">Request Body</dt>
        <dd>
          <pre class="mt-1 max-h-64 overflow-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200 font-mono">{{ formattedBody }}</pre>
        </dd>
      </div>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="handleExport">
        <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M9 7V2.2a2 2 0 0 0-.5.4l-4 3.9a2 2 0 0 0-.3.5H9Zm2 0V2h7a2 2 0 0 1 2 2v9.3l-2-2a1 1 0 0 0-1.4 1.4l.3.3h-6.6a1 1 0 1 0 0 2h6.6l-.3.3a1 1 0 0 0 1.4 1.4l2-2V20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd"></path>
        </svg>
        Export
      </Button>
      <Button variant="outline" @click="handleClose">
        Close
      </Button>
    </template>
  </Modal>
</template>

