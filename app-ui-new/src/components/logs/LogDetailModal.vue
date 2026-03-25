<script setup lang="ts">
import Modal from '@/components/ui/Modal.vue'
import LogDetailContent from './LogDetailContent.vue'
import type { LogEntry } from '@/types/logs'

defineProps<{
  modelValue: boolean
  log: LogEntry | null
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
}>()
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="log-detail-modal"
    title="Log Detail"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <LogDetailContent v-if="log" :log="log" />
  </Modal>
</template>
