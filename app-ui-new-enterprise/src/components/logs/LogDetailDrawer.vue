<script setup lang="ts">
import Drawer from '@/components/ui/Drawer.vue'
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
  <Drawer
    :model-value="modelValue"
    id="log-detail-drawer"
    label="Log Detail"
    width="w-96"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <LogDetailContent v-if="log" :log="log" />
  </Drawer>
</template>
